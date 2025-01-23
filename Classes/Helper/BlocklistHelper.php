<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Helper;

use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\UriInterface;

#[Flow\Scope('singleton')]
class BlocklistHelper
{
    #[Flow\Inject]
    protected ConfigurationHelper $configurationHelper;

    public function isUriInBlocklist(UriInterface $uri): bool
    {
        $path = $uri->getPath();
        foreach ($this->configurationHelper->getBlocklist() as $rawPattern => $active) {
            $pattern = '/' . str_replace('/', '\/', $rawPattern) . '/';

            if (! $active) {
                continue;
            }

            if (preg_match($pattern, $path) === 1) {
                return true;
            }
        }

        return false;
    }
}
