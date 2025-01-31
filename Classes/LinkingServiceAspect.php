<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting;

use Flowpack\SeoRouting\Enum\TrailingSlashModeEnum;
use Flowpack\SeoRouting\Helper\BlocklistHelper;
use Flowpack\SeoRouting\Helper\ConfigurationHelper;
use Flowpack\SeoRouting\Helper\TrailingSlashHelper;
use GuzzleHttp\Psr7\Exception\MalformedUriException;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Neos\Service\LinkingService;

#[Flow\Aspect]
class LinkingServiceAspect
{
    #[Flow\Inject]
    protected TrailingSlashHelper $trailingSlashHelper;

    #[Flow\Inject]
    protected ConfigurationHelper $configurationHelper;

    #[Flow\Inject]
    protected BlocklistHelper $blocklistHelper;

    /**
     * This ensures that all internal links are rendered with/without a trailing slash, depending on configuration.
     */
    #[Flow\Around('method(' . LinkingService::class . '->createNodeUri())')]
    public function handleTrailingSlashForNodeUri(JoinPointInterface $joinPoint): string
    {
        /** @var string $result */
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);

        if (! $this->configurationHelper->isTrailingSlashEnabled()) {
            return $result;
        }

        try {
            $uri = new Uri($result);
        } catch (MalformedUriException) {
            return $result;
        }

        if ($this->blocklistHelper->isUriInBlocklist($uri)) {
            return $result;
        }

        if ($this->configurationHelper->getTrailingSlashMode() === TrailingSlashModeEnum::ADD) {
            return (string)$this->trailingSlashHelper->appendTrailingSlash($uri);
        }

        return (string)$this->trailingSlashHelper->removeTrailingSlash($uri);
    }
}
