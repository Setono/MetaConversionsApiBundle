<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\ConsentChecker;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\Consent\ConsentCheckerInterface as ThirdPartyConsentCheckerInterface;
use Setono\Consent\DefaultConsents;
use Setono\MetaConversionsApiBundle\ConsentChecker\ConsentChecker;

#[CoversClass(ConsentChecker::class)]
final class ConsentCheckerTest extends TestCase
{
    #[Test]
    public function it_grants_when_consent_handling_is_disabled(): void
    {
        $checker = new ConsentChecker(false, DefaultConsents::CONSENT_MARKETING, self::thirdParty(false));

        self::assertTrue($checker->isGranted());
    }

    #[Test]
    public function it_grants_when_the_consent_bundle_is_not_installed(): void
    {
        $checker = new ConsentChecker(true, DefaultConsents::CONSENT_MARKETING, null);

        self::assertTrue($checker->isGranted());
    }

    #[Test]
    public function it_delegates_to_the_consent_bundle(): void
    {
        self::assertTrue((new ConsentChecker(true, DefaultConsents::CONSENT_MARKETING, self::thirdParty(true)))->isGranted());
        self::assertFalse((new ConsentChecker(true, DefaultConsents::CONSENT_MARKETING, self::thirdParty(false)))->isGranted());
    }

    #[Test]
    public function it_asks_for_the_configured_category(): void
    {
        $thirdParty = new class() implements ThirdPartyConsentCheckerInterface {
            /** @var list<string> */
            public array $asked = [];

            public function isGranted(string $consent): bool
            {
                $this->asked[] = $consent;

                return true;
            }
        };

        (new ConsentChecker(true, DefaultConsents::CONSENT_STATISTICAL, $thirdParty))->isGranted();

        self::assertSame([DefaultConsents::CONSENT_STATISTICAL], $thirdParty->asked);
    }

    private static function thirdParty(bool $granted): ThirdPartyConsentCheckerInterface
    {
        return new class($granted) implements ThirdPartyConsentCheckerInterface {
            public function __construct(private readonly bool $granted)
            {
            }

            public function isGranted(string $consent): bool
            {
                return $this->granted;
            }
        };
    }
}
