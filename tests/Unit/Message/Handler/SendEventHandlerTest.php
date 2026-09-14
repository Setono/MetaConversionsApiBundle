<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\Message\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Setono\MetaConversionsApi\Client\ClientInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApiBundle\AccessTokenResolver\AccessTokenResolverInterface;
use Setono\MetaConversionsApiBundle\Message\Command\SendEvent;
use Setono\MetaConversionsApiBundle\Message\Handler\SendEventHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[CoversClass(SendEventHandler::class)]
final class SendEventHandlerTest extends TestCase
{
    #[Test]
    public function it_sends_the_prepared_payload(): void
    {
        $message = new SendEvent('ViewContent', 'an-event-id', ['event_name' => 'ViewContent'], ['1234'], 'TEST1234');

        $sent = null;
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('sendEvent')->willReturnCallback(
            static function (Event $event) use (&$sent): void {
                $sent = $event;
            },
        );

        (new SendEventHandler($client, self::resolver(['1234' => 's3cr3t'])))($message);

        self::assertInstanceOf(Event::class, $sent);
        // The payload travelled through the transport ready to post, so it is handed to the client untouched
        self::assertSame(['event_name' => 'ViewContent'], $sent->getPayload());
        self::assertEquals([new Pixel('1234', 's3cr3t')], $sent->pixels);
        self::assertSame('TEST1234', $sent->testEventCode);
    }

    #[Test]
    public function it_skips_pixels_without_an_access_token(): void
    {
        $message = new SendEvent('ViewContent', 'an-event-id', [], ['no-token', '1234']);

        $sent = null;
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('sendEvent')->willReturnCallback(
            static function (Event $event) use (&$sent): void {
                $sent = $event;
            },
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with(self::stringContains('no access token'));

        (new SendEventHandler($client, self::resolver(['1234' => 's3cr3t']), $logger))($message);

        self::assertInstanceOf(Event::class, $sent);
        self::assertEquals([new Pixel('1234', 's3cr3t')], $sent->pixels);
    }

    #[Test]
    public function it_does_not_send_when_no_pixel_has_an_access_token(): void
    {
        $message = new SendEvent('ViewContent', 'an-event-id', [], ['no-token']);

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('sendEvent');

        (new SendEventHandler($client, self::resolver([])))($message);
    }

    /**
     * A custom PixelProviderInterface without a matching resolver turns into exactly this, and under a stock
     * production Monolog setup only an error survives the fingers_crossed buffer
     */
    #[Test]
    public function it_logs_an_error_when_no_pixel_can_be_used(): void
    {
        $message = new SendEvent('ViewContent', 'an-event-id', [], ['no-token']);

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('sendEvent');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');
        $logger->expects(self::once())->method('error')->with(self::stringContains('AccessTokenResolverInterface'));

        (new SendEventHandler($client, self::resolver([]), $logger))($message);
    }

    /**
     * 0.1.x serialised SendEvent with a single Event property. Messenger decodes such a body into the current class
     * with every property uninitialised, and retrying can never help
     */
    #[Test]
    public function it_rejects_a_message_serialised_by_a_previous_release(): void
    {
        $message = (new \ReflectionClass(SendEvent::class))->newInstanceWithoutConstructor();

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('sendEvent');

        $this->expectException(UnrecoverableMessageHandlingException::class);

        (new SendEventHandler($client, self::resolver([])))($message);
    }

    /**
     * @param array<array-key, string> $accessTokens
     */
    private static function resolver(array $accessTokens): AccessTokenResolverInterface
    {
        return new class($accessTokens) implements AccessTokenResolverInterface {
            /**
             * @param array<array-key, string> $accessTokens
             */
            public function __construct(private readonly array $accessTokens)
            {
            }

            public function resolve(string $pixelId): ?string
            {
                return $this->accessTokens[$pixelId] ?? null;
            }
        };
    }
}
