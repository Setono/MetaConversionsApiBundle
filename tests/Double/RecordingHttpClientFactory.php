<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Double;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Builds a MockHttpClient inside the container, since a container definition cannot hold a live object
 */
final class RecordingHttpClientFactory
{
    /** @var list<array{string, string}> */
    public static array $requests = [];

    public static function reset(): void
    {
        self::$requests = [];
    }

    public static function create(): MockHttpClient
    {
        return new MockHttpClient(static function (string $method, string $url): MockResponse {
            self::$requests[] = [$method, $url];

            return new MockResponse('{"events_received":1}');
        });
    }
}
