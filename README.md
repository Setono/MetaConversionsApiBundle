# Meta / Facebook Conversions API bundle

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]

Work with the Meta / Facebook Conversions API in your Symfony application. Under the hood this bundle integrates the
[Meta Conversions API PHP SDK](https://github.com/Setono/meta-conversions-api-php-sdk) library.

## Requirements

- PHP 8.1+
- Symfony 6.4 or 7.4
- A [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client and [PSR-17](https://www.php-fig.org/psr/psr-17/) factories
  (see [Installation](#installation))

## Installation

```shell
composer require setono/meta-conversions-api-bundle
```

The SDK sends events through a PSR-18 client and PSR-17 factories, which it discovers automatically. If your application
does not already ship both, install them alongside the bundle:

```shell
composer require setono/meta-conversions-api-bundle symfony/http-client nyholm/psr7
```

The SDK depends on [php-http/discovery](https://github.com/php-http/discovery), which contains a Composer plugin. Composer
asks whether to allow it, and either answer works. To skip the prompt (e.g. in CI) declare it in your `composer.json`:

```json
{
    "config": {
        "allow-plugins": {
            "php-http/discovery": false
        }
    }
}
```

Installing the bundle also installs the [Bot Detection Bundle](https://github.com/Setono/BotDetectionBundle), which is
used to filter bot requests.

If you want to handle consent (i.e. cookie/GDPR consent), install the [consent bundle](https://github.com/Setono/ConsentBundle)
and enable the `consent` option (see [Configuration](#configuration)):

```shell
composer require setono/consent-bundle
```

Upgrading from 0.1.x? See [UPGRADE.md](UPGRADE.md).

## Configuration

All options with their defaults:

```yaml
# config/packages/setono_meta_conversions_api.yaml
setono_meta_conversions_api:
    # Only track when the visitor has granted consent. Requires the consent bundle (see above)
    consent:
        enabled: false
        # The consent category that must be granted, see Setono\Consent\DefaultConsents
        category: marketing

    # Client side tracking, i.e. rendering the Meta pixel and fbq() calls in the browser.
    # Requires setono/tag-bag-bundle ^3.0 and defaults to enabled when that bundle is installed
    client_side:
        enabled: false

    # Server side tracking, i.e. sending the events to the Conversions API through Symfony Messenger
    server_side:
        enabled: true
        # The Messenger bus the SendEvent command is dispatched on. Defaults to your application's default bus
        message_bus: messenger.default_bus

    # The pixels to send events to (empty by default). Alternatively provide pixels from your own source by
    # aliasing Setono\MetaConversionsApiBundle\Provider\PixelProviderInterface to your own service.
    # The access token is only needed for server side tracking: client side tracking renders fbq() calls, which
    # only need the pixel id. A pixel without an access token is skipped server side, with a warning in the log
    pixels:
        - id: '%env(META_PIXEL_ID)%'
          access_token: '%env(META_ACCESS_TOKEN)%'

    # The PSR-18 http client used to send events. Defaults to Symfony's default http client, which means requests
    # to Meta show up in the profiler and honour the options you configured. Point it at a scoped client to give
    # Meta its own timeout
    http_client: psr18.http_client

    # Send events as test events, so they show up under 'Test events' in Meta's event manager instead of counting
    # as real conversions
    test_event_code:
        # If enabled, ?_testEventCode=... sets the test event code for the rest of the visitor's session.
        # Defaults to the value of kernel.debug, i.e. enabled in dev and disabled in prod
        query_parameter: '%kernel.debug%'
        # A static test event code applied to every event, e.g. on a staging environment
        value: null

    filters:
        # Regular expression fragments without delimiters, matched case insensitively. Events with a matching user
        # agent are not tracked. Invalid fragments, and fragments containing an unescaped '#', fail at compile time
        user_agent: []
```

### Route the command to a transport

Server side events are dispatched on your application's default Messenger bus. The bundle does not register a bus of
its own, so your bus configuration is left untouched. Point the bundle at another bus with the `server_side.message_bus`
option if you prefer.

**Route the command to an async transport.** Without it, Messenger handles the command synchronously, which means the
http call to Meta happens inside the visitor's request: their page waits for Meta's round trip, and Meta's
availability becomes your availability.

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        routing:
            'Setono\MetaConversionsApiBundle\Message\Command\SendEvent': async
```

With a transport, Messenger also retries a failed send and moves it to the failure transport when it keeps failing.

Either way, a send that fails is logged as an error and never propagates into the response, so an expired access
token or an outage at Meta cannot break the page.

## Usage

```php
<?php

declare(strict_types=1);

use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;

final class YourService
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public function track(): void
    {
        $event = new Event(Event::EVENT_VIEW_CONTENT);
        $event->customData->contentType = 'product';
        $event->customData->contentName = 'Blue Jeans';
        $event->customData->contentIds[] = 'PRODUCT_SKU';

        $this->eventDispatcher->dispatch(new ConversionsApiEventRaised($event));
    }
}
```

## How it works

Dispatching a `ConversionsApiEventRaised` runs the event through a pipeline of listeners. The bundle populates the
event first, then leaves a gap for your own listeners, then filters and sends:

| Priority                              | Listener                                          | What it does                                            |
|---------------------------------------|---------------------------------------------------|---------------------------------------------------------|
| `PRIORITY_POPULATE` (1000)            | `PopulateRequestPropertiesSubscriber`             | Source url, client ip and user agent from the request   |
| 900                                   | `PopulateFbpAndFbcPropertiesSubscriber`           | `fbp` and `fbc`                                         |
| 800                                   | `PopulateTestEventCodePropertySubscriber`         | Test event code                                         |
| 650                                   | `FilterEmptyUserAgentSubscriber`                  | Stops events without a user agent                       |
| 625                                   | `FilterConfiguredUserAgentsSubscriber`            | Stops events matching `filters.user_agent`              |
| `PRIORITY_FILTER` (600)               | `FilterBotsSubscriber`                            | Stops events from bots                                  |
| 500                                   | `PopulatePixelsSubscriber`                        | Pixels from the pixel provider                          |
| **`PRIORITY_ENRICH` (0)**             | **your listeners**                                | **Email, phone, external id, custom data**              |
| -950                                  | `StopPropagationIfNoPixelsHasBeenAddedSubscriber` | Stops events without pixels                             |
| `PRIORITY_SEND` (-1000)               | `AddEventToTagBagSubscriber`                      | Renders the `fbq()` calls (client side)                 |
| `PRIORITY_SEND` (-1000)               | `DispatchOnCommandBusSubscriber`                  | Dispatches `SendEvent` (server side)                    |

Two things follow from this:

- **Enrich at `PRIORITY_ENRICH`**, which is the default priority of any listener. Everything the bundle knows about
  the request is populated by then, and traffic the bundle does not want to track has already been discarded, so
  your listeners never do work for a bot.
- **A listener below `PRIORITY_ENRICH` may never run**, because propagation can already have been stopped.

The constants live on `ConversionsApiEventRaised`, so you can position your listener without hard coding a number.

### Enriching an event

Everything the Conversions API can do beyond the browser pixel comes from the user data you attach server side. Meta
normalises and hashes it for you, so set the raw values:

```php
<?php

declare(strict_types=1);

namespace App\EventListener;

use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(priority: ConversionsApiEventRaised::PRIORITY_ENRICH)]
final class AddCustomerToConversionsApiEvent
{
    public function __construct(private readonly Security $security)
    {
    }

    public function __invoke(ConversionsApiEventRaised $event): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $userData = $event->event->userData;
        $userData->email[] = $user->getEmail();
        $userData->firstName[] = $user->getFirstName();
        $userData->lastName[] = $user->getLastName();
        $userData->externalId[] = (string) $user->getId();
    }
}
```

You can also replace a step instead of adding to it: alias `PixelProviderInterface`, `FbpContextInterface` or
`FbcContextInterface` to your own service, or register a listener above the corresponding populate priority.

### Why did my event not show up?

Every listener that drops an event says so at debug level on the `setono_meta_conversions_api` Monolog channel: the
bot filter, the user agent filters, the no-pixels check, and each of the three consent gates. The send handler logs a
warning when a pixel has no access token.

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        meta:
            type: stream
            path: '%kernel.logs_dir%/meta.log'
            level: debug
            channels: ['setono_meta_conversions_api']
```

### Events that are not raised in a browser request

The pipeline assumes the event belongs to the request being handled. `PopulateRequestPropertiesSubscriber` therefore
fills in the source url, client ip and user agent of the current request, and the bot and user agent filters only
apply to events whose `actionSource` is `website` (the default).

For an event raised from a console command, a message handler or an incoming webhook, set another action source so
the filters leave it alone:

```php
$event = new Event(Event::EVENT_PURCHASE, Event::ACTION_SOURCE_SYSTEM_GENERATED);
```

If such an event is raised while handling an HTTP request, for instance a webhook from your payment provider, the
request properties still describe *that* request, not the customer. Overwrite them in a listener above
`PRIORITY_POPULATE` when they matter.

### Giving Meta its own timeout

Because the client is a normal service, a scoped client works out of the box:

```yaml
framework:
    http_client:
        scoped_clients:
            meta.client:
                base_uri: 'https://graph.facebook.com'
                timeout: 2
                max_duration: 5

setono_meta_conversions_api:
    http_client: meta.client
```

Note that a scoped client is a Symfony `HttpClientInterface`, so wrap it for PSR-18:

```yaml
services:
    meta.psr18_client:
        class: Symfony\Component\HttpClient\Psr18Client
        arguments: ['@meta.client']

setono_meta_conversions_api:
    http_client: meta.psr18_client
```

## Graph API version

Events are posted to the Graph API version of the installed `facebook/php-business-sdk` package (the SDK reads
`ApiConfig::APIVersion`): v26.0 with the 26.x package, v25.0 with 25.x. To move to a newer Graph API version simply run
`composer update facebook/php-business-sdk`.

## Test the integration

Take the test event code from Meta / Facebook's event manager and append it to any url on your website:
`https://example.com/?_testEventCode=[YOUR TEST EVENT CODE]` (or `?_test_event_code=[YOUR TEST EVENT CODE]`). The code
is saved in the session, so all your subsequent requests are sent with it. Clear it again with an empty value:
`https://example.com/?_testEventCode=`.

Because anyone who can add a query parameter would otherwise be able to divert their own conversions into the test
bucket, the query parameter is only honoured when `test_event_code.query_parameter` is enabled. It follows
`kernel.debug` by default, so it works in `dev` and is off in `prod`. To send *every* event as a test event, for
instance from a staging environment, set `test_event_code.value` instead.

[ico-version]: https://poser.pugx.org/setono/meta-conversions-api-bundle/v/stable
[ico-license]: https://poser.pugx.org/setono/meta-conversions-api-bundle/license
[ico-github-actions]: https://github.com/Setono/MetaConversionsApiBundle/workflows/build/badge.svg

[link-packagist]: https://packagist.org/packages/setono/meta-conversions-api-bundle
[link-github-actions]: https://github.com/Setono/MetaConversionsApiBundle/actions
