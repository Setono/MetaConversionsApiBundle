<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\EventSubscriber\PopulateTestEventCodePropertySubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[CoversClass(PopulateTestEventCodePropertySubscriber::class)]
final class PopulateTestEventCodePropertySubscriberTest extends TestCase
{
    #[Test]
    public function it_does_nothing_without_a_request(): void
    {
        $event = self::event();

        (new PopulateTestEventCodePropertySubscriber(new RequestStack()))->populate($event);

        self::assertNull($event->event->testEventCode);
    }

    #[Test]
    public function it_does_not_start_a_session_when_the_visitor_does_not_have_one(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('getName')->willReturn('PHPSESSID');
        // Calling get() would start the session, send a cookie to every visitor and make the response uncacheable
        $session->expects(self::never())->method('get');

        $request = new Request();
        $request->setSession($session);

        $event = self::event();

        (new PopulateTestEventCodePropertySubscriber(self::requestStack($request)))->populate($event);

        self::assertNull($event->event->testEventCode);
    }

    #[Test]
    public function it_populates_the_test_event_code_from_an_existing_session(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('getName')->willReturn('PHPSESSID');
        $session->method('get')->with('smca_test_event_code')->willReturn('TEST1234');

        $request = new Request([], [], [], ['PHPSESSID' => 'an-existing-session']);
        $request->setSession($session);

        $event = self::event();

        (new PopulateTestEventCodePropertySubscriber(self::requestStack($request)))->populate($event);

        self::assertSame('TEST1234', $event->event->testEventCode);
    }

    #[Test]
    public function it_ignores_a_non_string_value_in_the_session(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('getName')->willReturn('PHPSESSID');
        $session->method('get')->willReturn(null);

        $request = new Request([], [], [], ['PHPSESSID' => 'an-existing-session']);
        $request->setSession($session);

        $event = self::event();

        (new PopulateTestEventCodePropertySubscriber(self::requestStack($request)))->populate($event);

        self::assertNull($event->event->testEventCode);
    }

    private static function event(): ConversionsApiEventRaised
    {
        return new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT));
    }

    private static function requestStack(Request $request): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }
}
