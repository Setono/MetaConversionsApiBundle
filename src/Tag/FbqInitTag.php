<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tag;

use Setono\TagBag\Tag\ContentAwareInterface;
use Setono\TagBag\Tag\Tag;

final class FbqInitTag extends Tag implements ContentAwareInterface
{
    private string $init;

    private function __construct(string $init, int $priority)
    {
        $this->unique = true;
        $this->fingerprint = 'fbq_init';
        $this->init = $init;
        $this->priority = $priority;
    }

    /**
     * @param string $init The init code, i.e. <script>fbq('init'); ...</script>
     */
    public static function create(string $init, int $priority): self
    {
        return new self($init, $priority);
    }

    public function getContent(): string
    {
        return $this->init;
    }
}
