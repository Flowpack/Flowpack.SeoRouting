<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting;

use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RoutingMiddleware implements MiddlewareInterface
{
    #[Flow\Inject]
    protected ResponseFactoryInterface $responseFactory;

    #[Flow\Inject]
    protected UriFactoryInterface $uriFactory;

    /** @var array{enable: array{trailingSlash: bool, toLowerCase: bool}, statusCode?: int} */
    #[Flow\InjectConfiguration(path: 'redirect')]
    protected array $configuration;

    /** @var array{string: bool} */
    #[Flow\InjectConfiguration(path: 'blocklist')]
    protected array $blocklist;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isTrailingSlashEnabled = $this->configuration['enable']['trailingSlash'] ?? false;
        $isToLowerCaseEnabled = $this->configuration['enable']['toLowerCase'] ?? false;

        $uri = $request->getUri();

        if (! $isTrailingSlashEnabled && ! $isToLowerCaseEnabled) {
            return $handler->handle($request);
        }

        if ($this->matchesBlocklist($uri)) {
            return $handler->handle($request);
        }

        $oldPath = $uri->getPath();

        if ($isTrailingSlashEnabled) {
            $uri = $this->handleTrailingSlash($uri);
        }

        if ($isToLowerCaseEnabled) {
            $uri = $this->handleToLowerCase($uri);
        }

        if ($uri->getPath() === $oldPath) {
            return $handler->handle($request);
        }

        $response = $this->responseFactory->createResponse($this->configuration['statusCode'] ?? 301);

        return $response->withAddedHeader('Location', (string)$uri);
    }

    private function handleTrailingSlash(UriInterface $uri): UriInterface
    {
        if (strlen($uri->getPath()) === 0) {
            return $uri;
        }

        if (array_key_exists('extension', pathinfo($uri->getPath()))) {
            return $uri;
        }

        return $uri->withPath(rtrim($uri->getPath(), '/') . '/')
            ->withQuery($uri->getQuery())
            ->withFragment($uri->getFragment());
    }

    private function handleToLowerCase(UriInterface $uri): UriInterface
    {
        $loweredPath = strtolower($uri->getPath());

        if ($uri->getPath() === $loweredPath) {
            return $uri;
        }

        $newUri = str_replace($uri->getPath(), $loweredPath, (string)$uri);

        return $this->uriFactory->createUri($newUri);
    }

    private function matchesBlocklist(UriInterface $uri): bool
    {
        $path = $uri->getPath();
        foreach ($this->blocklist as $rawPattern => $active) {
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
