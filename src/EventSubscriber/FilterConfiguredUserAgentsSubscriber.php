<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class FilterConfiguredUserAgentsSubscriber implements EventSubscriberInterface
{
    /**
     * The compiled pattern, or null when no user agents are configured
     */
    private readonly ?string $pattern;

    private readonly LoggerInterface $logger;

    /**
     * @param list<string> $userAgents Regular expression fragments without delimiters
     *
     * @throws \InvalidArgumentException if the fragments do not compile into a valid regular expression
     */
    public function __construct(array $userAgents, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();

        if ([] === $userAgents) {
            $this->pattern = null;

            return;
        }

        $pattern = '#' . implode('|', $userAgents) . '#i';

        // Compiling once up front turns a typo into a boot failure instead of a filter that silently stops matching:
        // preg_match() returns false (not 1) for an invalid pattern, so the previous code just never filtered again
        if (false === @preg_match($pattern, '')) {
            throw new \InvalidArgumentException(sprintf(
                'The configured user agent filters do not compile into a valid regular expression: "%s". Remember to escape the "#" delimiter inside a fragment',
                $pattern,
            ));
        }

        $this->pattern = $pattern;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['filter', ConversionsApiEventRaised::PRIORITY_FILTER + 25],
        ];
    }

    public function filter(ConversionsApiEventRaised $event): void
    {
        if (null === $this->pattern) {
            return;
        }

        $userAgent = $event->event->userData->clientUserAgent;
        if (null === $userAgent) {
            return;
        }

        if (1 === preg_match($this->pattern, $userAgent)) {
            $this->logger->debug('The event {event_name} ({event_id}) was dropped because the user agent matches the configured filters: {user_agent}', [
                'event_name' => $event->event->eventName,
                'event_id' => $event->event->eventId,
                'user_agent' => $userAgent,
            ]);

            $event->stopPropagation();
        }
    }
}
