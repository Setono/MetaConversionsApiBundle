<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Client;

use Setono\MetaConversionsApi\Client\ClientInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Client implements ClientInterface
{
    private HttpClientInterface $httpClient;

    private SerializerInterface $serializer;

    private string $defaultAccessToken;

    public function __construct(
        HttpClientInterface $httpClient,
        SerializerInterface $serializer,
        string $defaultAccessToken
    ) {
        $this->httpClient = $httpClient;
        $this->serializer = $serializer;
        $this->defaultAccessToken = $defaultAccessToken;
    }

    public function sendEvent(Event $event): void
    {
        foreach ($event->pixels as $pixel) {
            $accessToken = $pixel->accessToken ?? $this->defaultAccessToken;

            $options = [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
                'body' => [
                    'access_token' => $accessToken,
                    'data' => $this->serializer->serialize($event),
                ],
            ];

            // todo allow test event code
//            if (null !== $this->testEventCode && '' !== $this->testEventCode) {
//                $options['body']['test_event_code'] = $this->testEventCode;
//            }

            $response = $this->httpClient->request(
                'POST',
                sprintf('https://graph.facebook.com/v13.0/%s/events', $pixel->id),
                $options
            );

            // todo error handling
//            Assert::same($response->getStatusCode(), 200, $this->getErrorMessage($response));
//            $content = $response->getContent(false);
//            $json = json_decode($content, true);
//            Assert::isArray($json);
        }
    }
}
