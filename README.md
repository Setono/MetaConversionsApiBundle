# Meta / Facebook Conversions API bundle

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]

Work with the Meta / Facebook Conversions API in your Symfony application

## Installation

To install this bundle, simply run:

```shell
composer require setono/meta-conversions-api-bundle
```

This will install the bundle and enable it if you're using Symfony Flex. If you're not using Flex, add the bundle
manually to `bundles.php` instead.

If you want to handle consent (i.e. cookie/gdpr consent), you can use the [consent bundle](https://github.com/Setono/ConsentBundle), by installing it:

```shell
composer require setono/consent-bundle
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
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
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

## Test the integration

To test the integration you can set the test event code (that you can retrieve from Meta / Facebooks event manager) and
append it to any url on your website like so: `https://example.com/?test_event_code=[YOUR TEST EVENT CODE]`. This code is
saved in a session and hence all your subsequent requests will be sent with the test event code.

[ico-version]: https://poser.pugx.org/setono/meta-conversions-api-bundle/v/stable
[ico-license]: https://poser.pugx.org/setono/meta-conversions-api-bundle/license
[ico-github-actions]: https://github.com/Setono/MetaConversionsApiBundle/workflows/build/badge.svg

[link-packagist]: https://packagist.org/packages/setono/meta-conversions-api-bundle
[link-github-actions]: https://github.com/Setono/MetaConversionsApiBundle/actions
