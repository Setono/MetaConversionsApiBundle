<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tag;

use Setono\TagBag\Tag\ContentAwareInterface;
use Setono\TagBag\Tag\Tag;

final class FbqInitTag extends Tag implements ContentAwareInterface
{
    private string $init;

    private function __construct(string $init)
    {
        $this->unique = true;
        $this->fingerprint = 'fbq_init';
        $this->init = $init;
    }

    /**
     * @param string $init The init code, i.e. <script>fbq('init'); ...</script>
     */
    public static function create(string $init): self
    {
        return new self($init);
    }

    public function getContent(): string
    {
        return $this->init;
    }
}
