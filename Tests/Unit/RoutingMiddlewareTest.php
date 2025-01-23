<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Tests\Unit;

use Flowpack\SeoRouting\Helper\BlocklistHelper;
use Flowpack\SeoRouting\Helper\ConfigurationHelper;
use Flowpack\SeoRouting\Helper\LowerCaseHelper;
use Flowpack\SeoRouting\Helper\TrailingSlashHelper;
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
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;

#[CoversClass(RoutingMiddleware::class)]
class RoutingMiddlewareTest extends TestCase
{
    private readonly RoutingMiddleware $routingMiddleware;
    private readonly ResponseFactoryInterface&MockObject $responseFactoryMock;
    private readonly ResponseInterface&MockObject $responseMock;
    private readonly ServerRequestInterface&MockObject $requestMock;
    private readonly RequestHandlerInterface&MockObject $requestHandlerMock;
    private readonly ConfigurationHelper&MockObject $configurationHelperMock;
    private readonly BlocklistHelper&MockObject $blocklistHelperMock;
    private readonly TrailingSlashHelper&MockObject $trailingSlashHelperMock;
    private readonly LowerCaseHelper&MockObject $lowerCaseHelperMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->routingMiddleware = new RoutingMiddleware();

        $this->responseFactoryMock = $this->createMock(ResponseFactoryInterface::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->requestMock = $this->createMock(ServerRequestInterface::class);
        $this->requestHandlerMock = $this->createMock(RequestHandlerInterface::class);
        $this->configurationHelperMock = $this->createMock(ConfigurationHelper::class);
        $this->blocklistHelperMock = $this->createMock(BlocklistHelper::class);
        $this->trailingSlashHelperMock = $this->createMock(TrailingSlashHelper::class);
        $this->lowerCaseHelperMock = $this->createMock(LowerCaseHelper::class);

        $routingMiddlewareReflection = new ReflectionClass($this->routingMiddleware);

        $property = $routingMiddlewareReflection->getProperty('responseFactory');
        $property->setValue($this->routingMiddleware, $this->responseFactoryMock);

        $property = $routingMiddlewareReflection->getProperty('configurationHelper');
        $property->setValue($this->routingMiddleware, $this->configurationHelperMock);

        $property = $routingMiddlewareReflection->getProperty('blocklistHelper');
        $property->setValue($this->routingMiddleware, $this->blocklistHelperMock);

        $property = $routingMiddlewareReflection->getProperty('trailingSlashHelper');
        $property->setValue($this->routingMiddleware, $this->trailingSlashHelperMock);

        $property = $routingMiddlewareReflection->getProperty('lowerCaseHelper');
        $property->setValue($this->routingMiddleware, $this->lowerCaseHelperMock);
    }


    #[DataProvider('urlsDataProvider')]
    public function testProcessShouldHandleUrlsCorrectly(
        string $originalUrl,
        string $expectedUrl,
        bool $isTrailingSlashEnabledResult,
        bool $isToLowerCaseEnabledResult,
        bool $isUriInBlocklistResult,
        int $statusCode
    ): void {
        $originalUri = new Uri($originalUrl);
        $expectedUri = new Uri($expectedUrl);

        $this->configurationHelperMock->method('isTrailingSlashEnabled')->willReturn($isTrailingSlashEnabledResult);
        $this->configurationHelperMock->method('isToLowerCaseEnabled')->willReturn($isToLowerCaseEnabledResult);
        $this->blocklistHelperMock->method('isUriInBlocklist')->willReturn($isUriInBlocklistResult);
        $this->trailingSlashHelperMock->method('appendTrailingSlash')->willReturn($expectedUri);
        $this->lowerCaseHelperMock->method('convertPathToLowerCase')->willReturn($expectedUri);
        $this->configurationHelperMock->method('getStatusCode')->willReturn($statusCode);

        $this->requestMock->expects($this->once())->method('getUri')->willReturn($originalUri);

        if ($originalUrl === $expectedUrl) {
            $this->requestHandlerMock->method('handle')->willReturn($this->responseMock);
        } else {
            $this->responseFactoryMock
                ->expects($this->once())
                ->method('createResponse')
                ->with($statusCode)
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
     * @return mixed[]
     */
    public static function urlsDataProvider(): array
    {
        return [
            [
                'originalUrl' => 'https://local.dev',
                'expectedUrl' => 'https://local.dev',
                'isTrailingSlashEnabledResult' => false,
                'isToLowerCaseEnabledResult' => false,
                'isUriInBlocklistResult' => false,
                'statusCode' => 301,
            ],
            [
                'originalUrl' => 'https://local.dev',
                'expectedUrl' => 'https://local.dev',
                'isTrailingSlashEnabledResult' => true,
                'isToLowerCaseEnabledResult' => false,
                'isUriInBlocklistResult' => false,
                'statusCode' => 302,
            ],
            [
                'originalUrl' => 'https://local.dev/test/test2',
                'expectedUrl' => 'https://local.dev/test/test2',
                'isTrailingSlashEnabledResult' => false,
                'isToLowerCaseEnabledResult' => true,
                'isUriInBlocklistResult' => true,
                'statusCode' => 301,
            ],
            [
                'originalUrl' => 'https://local.dev/test/test2',
                'expectedUrl' => 'https://local.dev/test/test2/',
                'isTrailingSlashEnabledResult' => false,
                'isToLowerCaseEnabledResult' => true,
                'isUriInBlocklistResult' => false,
                'statusCode' => 301,
            ],
        ];
    }
}
