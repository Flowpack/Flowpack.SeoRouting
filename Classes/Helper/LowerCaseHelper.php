<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Helper;

use Neos\Flow\Annotations\Scope;
use Psr\Http\Message\UriInterface;

#[Scope('singleton')]
class LowerCaseHelper
{
    public function convertPathToLowerCase(UriInterface $uri): UriInterface
    {
        $loweredPath = strtolower($uri->getPath());

        if ($uri->getPath() === $loweredPath) {
            return $uri;
        }

        // bypass links to files
        if (array_key_exists('extension', pathinfo($uri->getPath()))) {
            return $uri;
        }

        return $uri->withPath($loweredPath);
    }
}
