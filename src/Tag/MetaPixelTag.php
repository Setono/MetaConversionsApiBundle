<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tag;

use Setono\TagBag\Tag\ContentAwareInterface;
use Setono\TagBag\Tag\Tag;

final class MetaPixelTag extends Tag implements ContentAwareInterface
{
    private function __construct()
    {
        $this->unique = true;
        $this->fingerprint = 'meta_pixel';
    }

    public static function create(): self
    {
        return new self();
    }

    public function getContent(): string
    {
        return <<<META_PIXEL
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
</script>
<noscript>
  <img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={your-pixel-id-goes-here}&ev=PageView&noscript=1">
</noscript>
META_PIXEL;
    }
}
