<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting;

use Flowpack\SeoRouting\Enum\TrailingSlashModeEnum;
use Flowpack\SeoRouting\Helper\BlocklistHelper;
use Flowpack\SeoRouting\Helper\ConfigurationHelper;
use Flowpack\SeoRouting\Helper\LowerCaseHelper;
use Flowpack\SeoRouting\Helper\TrailingSlashHelper;
use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RoutingMiddleware implements MiddlewareInterface
{
    #[Flow\Inject]
    protected ResponseFactoryInterface $responseFactory;

    #[Flow\Inject]
    protected ConfigurationHelper $configurationHelper;

    #[Flow\Inject]
    protected BlocklistHelper $blocklistHelper;

    #[Flow\Inject]
    protected TrailingSlashHelper $trailingSlashHelper;

    #[Flow\Inject]
    protected LowerCaseHelper $lowerCaseHelper;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isTrailingSlashEnabled = $this->configurationHelper->isTrailingSlashEnabled();
        $isToLowerCaseEnabled = $this->configurationHelper->isToLowerCaseEnabled();

        $uri = $request->getUri();

        if (! $isTrailingSlashEnabled && ! $isToLowerCaseEnabled) {
            return $handler->handle($request);
        }

        if ($this->blocklistHelper->isUriInBlocklist($uri)) {
            return $handler->handle($request);
        }

        $oldPath = $uri->getPath();

        if ($isTrailingSlashEnabled) {
            match ($this->configurationHelper->getTrailingSlashMode()) {
                TrailingSlashModeEnum::ADD => $uri = $this->trailingSlashHelper->appendTrailingSlash($uri),
                TrailingSlashModeEnum::REMOVE => $uri = $this->trailingSlashHelper->removeTrailingSlash($uri),
            };
        }

        if ($isToLowerCaseEnabled) {
            $uri = $this->lowerCaseHelper->convertPathToLowerCase($uri);
        }

        if ($uri->getPath() === $oldPath) {
            return $handler->handle($request);
        }

        $response = $this->responseFactory->createResponse($this->configurationHelper->getStatusCode());

        return $response->withAddedHeader('Location', (string)$uri);
    }
}
