<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\Message\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Setono\MetaConversionsApi\Client\ClientInterface;
use Setono\MetaConversionsApi\Client\ErrorResponse;
use Setono\MetaConversionsApi\Event\PreparedEvent;
use Setono\MetaConversionsApi\Exception\InvalidArgumentException;
use Setono\MetaConversionsApi\Exception\ResponseException;
use Setono\MetaConversionsApi\Exception\TransportException;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApiBundle\Message\Command\SendEvent;
use Setono\MetaConversionsApiBundle\Message\Handler\SendEventHandler;
use Setono\MetaConversionsApiBundle\Tests\Double\Doubles;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[CoversClass(SendEventHandler::class)]
final class SendEventHandlerTest extends TestCase
{
    #[Test]
    public function it_sends_the_prepared_event_with_its_access_token_back(): void
    {
        $sent = null;
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('sendEvent');
        $client->expects(self::once())->method('sendPreparedEvent')->willReturnCallback(
            static function (PreparedEvent $preparedEvent) use (&$sent): void {
                $sent = $preparedEvent;
            },
        );

        (new SendEventHandler($client, Doubles::pixelProvider([new Pixel('1234', 's3cr3t')])))(self::message(['1234']));

        self::assertInstanceOf(PreparedEvent::class, $sent);
        // The payload travelled through the transport ready to post, so it is handed to the client untouched
        self::assertSame(['event_name' => 'ViewContent'], $sent->payload);
        self::assertSame('an-event-id', $sent->eventId);
        self::assertSame('TEST1234', $sent->testEventCode);
        // ... while the access token, which never travelled, is back
        self::assertEquals([new Pixel('1234', 's3cr3t')], $sent->pixels);
    }

    /**
     * Skipping a pixel without an access token is the SDK client's job, so such a pixel has to reach it as it is: one
     * the provider does not know about, and one it knows but has no token for, which is what an unset env var gives
     */
    #[Test]
    public function it_leaves_pixels_without_an_access_token_to_the_sdk(): void
    {
        $sent = null;
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('sendPreparedEvent')->willReturnCallback(
            static function (PreparedEvent $preparedEvent) use (&$sent): void {
                $sent = $preparedEvent;
            },
        );

        $provider = Doubles::pixelProvider([new Pixel('5678'), new Pixel('1234', 's3cr3t')]);

        (new SendEventHandler($client, $provider))(self::message(['unknown', '5678', '1234']));

        self::assertInstanceOf(PreparedEvent::class, $sent);
        self::assertEquals([new Pixel('unknown'), new Pixel('5678'), new Pixel('1234', 's3cr3t')], $sent->pixels);
    }

    /**
     * Messenger retries whatever a handler throws, three times by default, unless it is told not to. These are the
     * failures a retry cannot fix
     */
    #[Test]
    #[DataProvider('failuresARetryCannotFix')]
    public function it_tells_messenger_not_to_retry(\Throwable $failure): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendPreparedEvent')->willThrowException($failure);

        try {
            (new SendEventHandler($client, Doubles::pixelProvider([new Pixel('1234', 's3cr3t')])))(self::message(['1234']));
            self::fail('Expected an UnrecoverableMessageHandlingException');
        } catch (UnrecoverableMessageHandlingException $e) {
            // The reason must not be lost on the way to the failure transport
            self::assertSame($failure, $e->getPrevious());
            self::assertSame($failure->getMessage(), $e->getMessage());
        }
    }

    /**
     * @return iterable<string, array{\Throwable}>
     */
    public static function failuresARetryCannotFix(): iterable
    {
        yield 'invalid input' => [new InvalidArgumentException('The payload cannot be encoded as JSON')];

        // What the SDK client throws when server side tracking is on but no access token is configured at all
        yield 'none of the pixels has an access token' => [new InvalidArgumentException('The event was not sent to Meta/Facebook because none of its pixels has an access token: 1234')];

        yield 'meta rejects the request' => [new ResponseException(
            400,
            '{}',
            ErrorResponse::fromJson('{"error":{"message":"Invalid OAuth access token","type":"OAuthException","code":190,"fbtrace_id":"x"}}'),
        )];

        yield 'meta says explicitly that it is not transient' => [new ResponseException(
            400,
            '{}',
            ErrorResponse::fromJson('{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"is_transient":false,"fbtrace_id":"x"}}'),
        )];

        yield 'a client error from a proxy' => [new ResponseException(403, 'Forbidden', null)];
    }

    /**
     * ... and these are the ones it may well fix, so they reach Messenger untouched
     */
    #[Test]
    #[DataProvider('failuresARetryMayFix')]
    public function it_lets_messenger_retry(\Throwable $failure): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendPreparedEvent')->willThrowException($failure);

        try {
            (new SendEventHandler($client, Doubles::pixelProvider([new Pixel('1234', 's3cr3t')])))(self::message(['1234']));
            self::fail('Expected the failure to propagate');
        } catch (\Throwable $e) {
            self::assertSame($failure, $e);
        }
    }

    /**
     * @return iterable<string, array{\Throwable}>
     */
    public static function failuresARetryMayFix(): iterable
    {
        yield 'the request never got a response' => [new TransportException(
            new class('Connection timed out') extends \RuntimeException implements ClientExceptionInterface {
            },
        )];

        yield 'a server error at meta' => [new ResponseException(503, 'Service Unavailable', null)];

        yield 'an error meta flags as transient' => [new ResponseException(
            400,
            '{}',
            ErrorResponse::fromJson('{"error":{"message":"Application request limit reached","type":"OAuthException","code":4,"is_transient":true,"fbtrace_id":"x"}}'),
        )];
    }

    /**
     * @param list<string> $pixelIds
     */
    private static function message(array $pixelIds): SendEvent
    {
        return new SendEvent(new PreparedEvent(
            'ViewContent',
            'an-event-id',
            ['event_name' => 'ViewContent'],
            array_map(static fn (string $pixelId): Pixel => new Pixel($pixelId), $pixelIds),
            'TEST1234',
        ));
    }
}
