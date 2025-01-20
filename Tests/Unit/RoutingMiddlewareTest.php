<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Tests\Unit;

use Flowpack\SeoRouting\RoutingMiddleware;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionException;

#[CoversClass(RoutingMiddleware::class)]
class RoutingMiddlewareTest extends TestCase
{
    private readonly RoutingMiddleware $routingMiddleware;
    /** @var ReflectionClass<RoutingMiddleware> */
    private readonly ReflectionClass $routingMiddlewareReflection;
    private readonly ResponseFactoryInterface&MockObject $responseFactoryMock;
    private readonly ResponseInterface&MockObject $responseMock;
    private readonly UriFactoryInterface&MockObject $uriFactoryMock;
    private readonly ServerRequestInterface&MockObject $requestMock;
    private readonly RequestHandlerInterface&MockObject $requestHandlerMock;

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->routingMiddleware = new RoutingMiddleware();

        $this->responseFactoryMock = $this->createMock(ResponseFactoryInterface::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->uriFactoryMock = $this->createMock(UriFactoryInterface::class);
        $this->requestMock = $this->createMock(ServerRequestInterface::class);
        $this->requestHandlerMock = $this->createMock(RequestHandlerInterface::class);

        $this->routingMiddlewareReflection = new ReflectionClass($this->routingMiddleware);

        $property = $this->routingMiddlewareReflection->getProperty('responseFactory');
        $property->setValue($this->routingMiddleware, $this->responseFactoryMock);

        $property = $this->routingMiddlewareReflection->getProperty('uriFactory');
        $property->setValue($this->routingMiddleware, $this->uriFactoryMock);

        $this->injectBlocklist([]);
    }

    /**
     * @param  array{enable: array{trailingSlash: bool, toLowerCase: bool}, statusCode?: int}  $configuration
     * @param  array{string: bool}  $blocklist
     */
    #[DataProvider('urlsWithConfigAndBlocklist')]
    public function testProcessShouldHandleUrlsCorrectly(
        string $originalUrl,
        string $expectedUrl,
        array $configuration,
        array $blocklist,
    ): void {
        $this->injectConfiguration($configuration);
        $this->injectBlocklist($blocklist);

        /*
         * We're not using a Mock here because it wouldn't make sense and we couldn't really test whether the code
         * works, the test would just consist of a lot of expect assertions.
         */
        $originalUri = new Uri($originalUrl);
        $expectedUri = new Uri($expectedUrl);


        $this->requestMock->expects($this->once())->method('getUri')->willReturn($originalUri);
        $this->uriFactoryMock->method('createUri')->willReturn($expectedUri);

        if ($originalUrl === $expectedUrl) {
            $this->requestHandlerMock->method('handle')->willReturn($this->responseMock);
        } else {
            $this->responseFactoryMock
                ->expects($this->once())
                ->method('createResponse')
                ->with($configuration['statusCode'] ?? 301)
                ->willReturn($this->responseMock);

            $this->responseMock
                ->expects($this->once())
                ->method('withAddedHeader')
                ->with('Location', (string)$expectedUri)
                ->willReturnSelf();
        }

        self::assertSame(
            $this->responseMock,
            $this->routingMiddleware->process($this->requestMock, $this->requestHandlerMock)
        );
    }

    /**
     * @param  array{enable: array{trailingSlash: bool, toLowerCase: bool}, statusCode?: int}  $configuration
     */
    private function injectConfiguration(array $configuration): void
    {
        $property = $this->routingMiddlewareReflection->getProperty('configuration');
        $property->setValue($this->routingMiddleware, $configuration);
    }

    /**
     * @param  array{string: bool}|array{}  $blocklist
     */
    private function injectBlocklist(array $blocklist): void
    {
        $property = $this->routingMiddlewareReflection->getProperty('blocklist');
        $property->setValue($this->routingMiddleware, $blocklist);
    }

    /**
     * @return mixed[]
     */
    public static function urlsWithConfigAndBlocklist(): array
    {
        /*
         * originalUrl, expectedUrl, configuration, blocklist
         */
        return [
            [
                'https://local.dev',
                'https://local.dev',
                ['enable' => ['trailingSlash' => false, 'toLowerCase' => false]],
                [],
            ],
            [
                'https://local.dev',
                'https://local.dev',
                ['enable' => ['trailingSlash' => true, 'toLowerCase' => false], 'statusCode' => 302],
                [],
            ],
            [
                'https://local.dev/test/test2',
                'https://local.dev/test/test2/',
                ['enable' => ['trailingSlash' => true, 'toLowerCase' => false,],],
                [],
            ],
            [
                'https://local.dev/public/css/main.css',
                'https://local.dev/public/css/main.css',
                ['enable' => ['trailingSlash' => true, 'toLowerCase' => false,],],
                [],
            ],
            [
                'https://local.dev/test?foo=bar&bar=baz',
                'https://local.dev/test/?foo=bar&bar=baz',
                ['enable' => ['trailingSlash' => true, 'toLowerCase' => false,],],
                [],
            ],
            [
                'https://local.dev/test/',
                'https://local.dev/test/',
                ['enable' => ['trailingSlash' => true, 'toLowerCase' => false,],],
                [],
            ],
            [
                'https://local.dev/test/test2?foo=bar&bar=baz#hash',
                'https://local.dev/test/test2/?foo=bar&bar=baz#hash',
                ['enable' => ['trailingSlash' => true, 'toLowerCase' => false,],],
                [],
            ],
            [
                'https://local.dev/camelCase/?foo=bar&bar=baz#hash',
                'https://local.dev/camelcase/?foo=bar&bar=baz#hash',
                ['enable' => ['trailingSlash' => false, 'toLowerCase' => true]],
                [],
            ],
            [
                'https://local.dev/nocaps',
                'https://local.dev/nocaps',
                ['enable' => ['trailingSlash' => false, 'toLowerCase' => true]],
                [],
            ],
            [
                'https://local.dev/CAPSLOCK?foo=bar&bar=baz#hash',
                'https://local.dev/capslock/?foo=bar&bar=baz#hash',
                ['enable' => ['trailingSlash' => true, 'toLowerCase' => true]],
                [],
            ],
            [
                'https://local.dev/neos',
                'https://local.dev/neos',
                ['enable' => ['trailingSlash' => true, 'toLowerCase' => true]],
                [
                    '/neos.*' => true,
                ],
            ],
            [
                'https://local.dev/NEOS',
                'https://local.dev/neos/',
                ['enable' => ['trailingSlash' => true, 'toLowerCase' => true]],
                [
                    '/neos.*' => false,
                ],
            ],
        ];
    }
}
