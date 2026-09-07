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

Server side events are dispatched on your application's default Messenger bus. The bundle does not register a bus of
its own, so your bus configuration is left untouched. Point the bundle at another bus with the `server_side.message_bus`
option if you prefer.

Events are handled synchronously unless you route the command to a transport, which is the recommended setup:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        routing:
            'Setono\MetaConversionsApiBundle\Message\Command\SendEvent': async
```

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
