<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\TestEventCode\TestEventCode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Stores the test event code from the query string in the session
 *
 * This subscriber is only registered when the test_event_code.query_parameter option is enabled, because anyone
 * able to add a query parameter can otherwise divert their own conversions into Meta's test bucket
 */
final class StoreTestEventCodeSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'store',
        ];
    }

    public function store(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // An application without session support would throw a SessionNotFoundException below, and any visitor
        // could trigger that by appending the query parameter to a url
        if (!$request->hasSession()) {
            return;
        }

        $present = false;
        $testEventCode = null;

        foreach (TestEventCode::QUERY_PARAMETERS as $queryParameter) {
            if (!$request->query->has($queryParameter)) {
                continue;
            }

            $present = true;

            $value = $request->query->get($queryParameter);
            if (is_string($value) && '' !== $value) {
                $testEventCode = $value;

                break;
            }
        }

        if (!$present) {
            return;
        }

        $session = $request->getSession();

        // An empty value, i.e. ?_testEventCode=, stops sending the test event code again
        if (null === $testEventCode) {
            $session->remove(TestEventCode::SESSION_KEY);

            return;
        }

        $session->set(TestEventCode::SESSION_KEY, $testEventCode);
    }
}
