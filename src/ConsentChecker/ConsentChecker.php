<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\ConsentChecker;

use Setono\Consent\ConsentCheckerInterface as ThirdPartyConsentCheckerInterface;

final class ConsentChecker implements ConsentCheckerInterface
{
    public function __construct(
        private readonly bool $consentEnabled,
        private readonly string $consentCategory,
        private readonly ?ThirdPartyConsentCheckerInterface $consentChecker,
    ) {
    }

    public function isGranted(): bool
    {
        if (!$this->consentEnabled) {
            return true;
        }

        if (null === $this->consentChecker) {
            return true;
        }

        return $this->consentChecker->isGranted($this->consentCategory);
    }
}
