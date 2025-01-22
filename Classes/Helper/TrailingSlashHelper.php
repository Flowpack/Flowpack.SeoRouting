<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Helper;

use Neos\Flow\Annotations\Scope;
use Psr\Http\Message\UriInterface;

#[Scope('singleton')]
class TrailingSlashHelper
{
    public function appendTrailingSlash(UriInterface $uri): UriInterface
    {
        // bypass links without path
        if (strlen($uri->getPath()) === 0) {
            return $uri;
        }

        // bypass links to files
        if (array_key_exists('extension', pathinfo($uri->getPath()))) {
            return $uri;
        }

        // bypass mailto and tel links
        if (in_array($uri->getScheme(), ['mailto', 'tel'], true)) {
            return $uri;
        }

        return $uri->withPath(rtrim($uri->getPath(), '/') . '/');
    }
}
