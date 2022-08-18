<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class FilterConfiguredUserAgentsSubscriber implements EventSubscriberInterface
{
    /** @var list<string> */
    private array $userAgents;

    /**
     * @param list<string> $userAgents
     */
    public function __construct(array $userAgents)
    {
        $this->userAgents = $userAgents;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['filter', -875],
        ];
    }

    public function filter(ConversionsApiEventRaised $event): void
    {
        if ([] === $this->userAgents) {
            return;
        }

        $ua = $event->event->userData->clientUserAgent;
        if (null === $ua) {
            return;
        }
        $regex = '#' . implode('|', $this->userAgents) . '#';
        if (preg_match($regex, $ua) === 1) {
            $event->stopPropagation();
        }
    }
}
