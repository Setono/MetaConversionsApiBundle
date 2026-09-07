# UPGRADE FROM 0.1.x TO 1.0

## Requirements

- PHP 8.1+ (was 7.4) and Symfony 6.4 or 7.4 (Symfony 5.4, 6.0–6.3 and 7.0–7.3 are no longer supported).
- `setono/meta-conversions-api-php-sdk` `^1.0` (was `^0.2.1`). Consequences:
    - Events are posted to Graph API **v25.0 / v26.0** (whichever `facebook/php-business-sdk` is installed) instead of
      v14.0. The payloads are unchanged.
    - The SDK needs a [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client and [PSR-17](https://www.php-fig.org/psr/psr-17/)
      factories and discovers them automatically. If `composer update` complains about `psr/http-client-implementation`
      or `psr/http-factory-implementation`, install e.g. `composer require symfony/http-client nyholm/psr7`.
      `kriswallsmith/buzz` + `nyholm/psr7` keep working if you already have them.
    - `php-http/discovery` ships a Composer plugin. Add `"php-http/discovery": false` (or `true`) to
      `config.allow-plugins` in your `composer.json` to avoid the interactive prompt.
    - `Client::setResponseFactory()` was removed from the SDK. Drop the call if you configured the client manually.
- `setono/consent-contracts` is now a required dependency. The consent *bundle* (`setono/consent-bundle`) remains optional.
- `symfony/monolog-bundle` is no longer required by the bundle. The SDK client is wired to the `logger` service when
  it exists.

## Service ids are now FQCNs

All `setono_meta_conversions_api.*` service ids were replaced by class/interface names. Update your service
definitions, decorators and aliases:

| 0.1.x                                                              | 1.0                                                                          |
|--------------------------------------------------------------------|------------------------------------------------------------------------------|
| `setono_meta_conversions_api.client.default`                       | `Setono\MetaConversionsApi\Client\Client`                                    |
| `setono_meta_conversions_api.pixel_provider.default`               | `Setono\MetaConversionsApiBundle\Provider\PixelProviderInterface` (alias)    |
| `setono_meta_conversions_api.pixel_provider.configuration_based`   | `Setono\MetaConversionsApiBundle\Provider\ConfigurationBasedPixelProvider`   |
| `setono_meta_conversions_api.generator.fbq`                        | `Setono\MetaConversionsApi\Generator\FbqGenerator`                           |
| `setono_meta_conversions_api.message_handler.send_event`           | `Setono\MetaConversionsApiBundle\Message\Handler\SendEventHandler`           |
| `setono_meta_conversions_api.context.fbc`                          | `Setono\MetaConversionsApiBundle\Context\Fbc\FbcContextInterface` (alias)    |
| `setono_meta_conversions_api.context.fbc.cached`                   | `Setono\MetaConversionsApiBundle\Context\Fbc\CachedFbcContext`               |
| `setono_meta_conversions_api.context.fbc.cookie_based`             | `Setono\MetaConversionsApiBundle\Context\Fbc\CookieBasedFbcContext`          |
| `setono_meta_conversions_api.context.fbc.query_based`              | `Setono\MetaConversionsApiBundle\Context\Fbc\QueryBasedFbcContext`           |
| `setono_meta_conversions_api.context.fbp`                          | `Setono\MetaConversionsApiBundle\Context\Fbp\FbpContextInterface` (alias)    |
| `setono_meta_conversions_api.context.fbp.cached`                   | `Setono\MetaConversionsApiBundle\Context\Fbp\CachedFbpContext`               |
| `setono_meta_conversions_api.context.fbp.cookie_based`             | `Setono\MetaConversionsApiBundle\Context\Fbp\CookieBasedFbpContext`          |
| `setono_meta_conversions_api.context.fbp.generated`                | `Setono\MetaConversionsApiBundle\Context\Fbp\GeneratedFbpContext`            |
| `setono_meta_conversions_api.event_subscriber.<name>`              | `Setono\MetaConversionsApiBundle\EventSubscriber\<Name>Subscriber`           |

To provide pixels from your own source, alias the interface to your service:

```yaml
services:
    Setono\MetaConversionsApiBundle\Provider\PixelProviderInterface: '@App\Provider\MyPixelProvider'
```

## Consent handling

- New option `consent.category` (default `marketing`, see `Setono\Consent\DefaultConsents`) selects the consent
  category that must be granted.
- Consent is now evaluated through `Setono\MetaConversionsApiBundle\ConsentChecker\ConsentCheckerInterface`. The
  default implementation grants when consent is disabled or the consent bundle is not installed, and otherwise asks
  `Setono\Consent\ConsentCheckerInterface` for the configured category.
- The event subscribers `DispatchOnCommandBusSubscriber`, `AddEventToTagBagSubscriber`, `AddLibraryToTagBagSubscriber`,
  `StoreFbcSubscriber` and `StoreFbpSubscriber` take a `ConsentCheckerInterface` instead of the previous
  `?ConsentContextInterface $consentContext` and `bool $consentEnabled` / `bool $clientSideEnabled` /
  `bool $serverSideEnabled` arguments. Adapt subclasses, decorators and custom service definitions.

## Conditional services

Services are only registered when the corresponding side is enabled, instead of being registered and checking a flag
at runtime:

- `client_side.enabled`: `AddEventToTagBagSubscriber`, `AddLibraryToTagBagSubscriber`
- `server_side.enabled`: `DispatchOnCommandBusSubscriber`, `Message\Handler\SendEventHandler`

Do not reference these services when the respective side is disabled.
