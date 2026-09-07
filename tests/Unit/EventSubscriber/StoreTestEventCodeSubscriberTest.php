<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApiBundle\EventSubscriber\StoreTestEventCodeSubscriber;
use Setono\MetaConversionsApiBundle\TestEventCode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[CoversClass(StoreTestEventCodeSubscriber::class)]
final class StoreTestEventCodeSubscriberTest extends TestCase
{
    #[Test]
    public function it_does_not_throw_when_the_application_has_no_session(): void
    {
        $event = self::event(new Request(['_testEventCode' => 'TEST1234']));

        (new StoreTestEventCodeSubscriber())->store($event);

        self::assertFalse($event->getRequest()->hasSession());
    }

    #[Test]
    public function it_stores_the_test_event_code(): void
    {
        $session = self::session();
        $event = self::event(self::request(['_testEventCode' => 'TEST1234'], $session));

        (new StoreTestEventCodeSubscriber())->store($event);

        self::assertSame('TEST1234', $session->get(TestEventCode::SESSION_KEY));
    }

    #[Test]
    public function it_stores_the_test_event_code_from_the_snake_cased_parameter(): void
    {
        $session = self::session();
        $event = self::event(self::request(['_test_event_code' => 'TEST1234'], $session));

        (new StoreTestEventCodeSubscriber())->store($event);

        self::assertSame('TEST1234', $session->get(TestEventCode::SESSION_KEY));
    }

    #[Test]
    public function it_removes_the_test_event_code_when_the_parameter_is_empty(): void
    {
        $session = self::session();
        $session->set(TestEventCode::SESSION_KEY, 'TEST1234');

        $event = self::event(self::request(['_testEventCode' => ''], $session));

        (new StoreTestEventCodeSubscriber())->store($event);

        self::assertFalse($session->has(TestEventCode::SESSION_KEY));
    }

    #[Test]
    public function it_leaves_the_session_alone_without_the_query_parameter(): void
    {
        $session = self::session();
        $session->set(TestEventCode::SESSION_KEY, 'TEST1234');

        $event = self::event(self::request([], $session));

        (new StoreTestEventCodeSubscriber())->store($event);

        self::assertSame('TEST1234', $session->get(TestEventCode::SESSION_KEY));
    }

    #[Test]
    public function it_ignores_sub_requests(): void
    {
        $session = self::session();
        $event = self::event(self::request(['_testEventCode' => 'TEST1234'], $session), HttpKernelInterface::SUB_REQUEST);

        (new StoreTestEventCodeSubscriber())->store($event);

        self::assertFalse($session->has(TestEventCode::SESSION_KEY));
    }

    private static function session(): Session
    {
        return new Session(new MockArraySessionStorage());
    }

    /**
     * @param array<string, string> $query
     */
    private static function request(array $query, Session $session): Request
    {
        $request = new Request($query);
        $request->setSession($session);

        return $request;
    }

    private static function event(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent(
            self::createStub(HttpKernelInterface::class),
            $request,
            $requestType,
        );
    }
}
