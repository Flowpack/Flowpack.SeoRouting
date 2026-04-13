<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Tests\Unit;

use Flowpack\SeoRouting\Enum\TrailingSlashModeEnum;
use Flowpack\SeoRouting\Exceptions\Http\Exception as HttpException;
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
use Throwable;

#[CoversClass(RoutingMiddleware::class)]
#[CoversClass(HttpException::class)]
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


    /**
     * @param class-string<Throwable>|null $expectedException
     * @throws Exception
     * @throws HttpException
     */
    #[DataProvider('urlsDataProvider')]
    public function testProcessShouldHandleUrlsCorrectly(
        string $originalUrl,
        string $expectedUrl,
        bool $isTrailingSlashEnabledResult,
        bool $isToLowerCaseEnabledResult,
        bool $isUriInBlocklistResult,
        int $statusCode,
        TrailingSlashModeEnum $trailingSlashMode,
        int $handlerStatusCode = 200,
        ?string $expectedException = null,
    ): void {
        $originalUri = new Uri($originalUrl);
        $expectedUri = new Uri($expectedUrl);

        $this->configurationHelperMock->method('isTrailingSlashEnabled')->willReturn($isTrailingSlashEnabledResult);
        $this->configurationHelperMock->method('isToLowerCaseEnabled')->willReturn($isToLowerCaseEnabledResult);
        $this->blocklistHelperMock->method('isUriInBlocklist')->willReturn($isUriInBlocklistResult);
        $this->trailingSlashHelperMock->method('appendTrailingSlash')->willReturn($expectedUri);
        $this->trailingSlashHelperMock->method('removeTrailingSlash')->willReturn($expectedUri);
        $this->lowerCaseHelperMock->method('convertPathToLowerCase')->willReturn($expectedUri);
        $this->configurationHelperMock->method('getStatusCode')->willReturn($statusCode);
        $this->configurationHelperMock->method('getTrailingSlashMode')->willReturn($trailingSlashMode);

        $this->requestMock->expects($this->once())->method('getUri')->willReturn($originalUri);

        $pathChanged = $originalUrl !== $expectedUrl;
        if (is_string($expectedException)) {
            $this->expectException($expectedException);
            $this->responseFactoryMock->expects($this->never())->method('createResponse');
            $this->routingMiddleware->process($this->requestMock, $this->requestHandlerMock);
            return;
        } elseif (!$pathChanged) {
            $this->requestHandlerMock->method('handle')->willReturn($this->responseMock);
        } elseif ($handlerStatusCode >= 400) {
            $this->responseMock->method('getStatusCode')->willReturn($handlerStatusCode);
            $this->requestHandlerMock->method('handle')->willReturn($this->responseMock);
            $this->responseFactoryMock->expects($this->never())->method('createResponse');
        } else {
            $handlerResponseMock = $this->createMock(ResponseInterface::class);
            $handlerResponseMock->method('getStatusCode')->willReturn($handlerStatusCode);
            $this->requestHandlerMock->method('handle')->willReturn($handlerResponseMock);

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
                'trailingSlashMode' => TrailingSlashModeEnum::ADD,
            ],
            [
                'originalUrl' => 'https://local.dev',
                'expectedUrl' => 'https://local.dev',
                'isTrailingSlashEnabledResult' => true,
                'isToLowerCaseEnabledResult' => false,
                'isUriInBlocklistResult' => false,
                'statusCode' => 302,
                'trailingSlashMode' => TrailingSlashModeEnum::ADD,
            ],
            [
                'originalUrl' => 'https://local.dev/test/test2',
                'expectedUrl' => 'https://local.dev/test/test2',
                'isTrailingSlashEnabledResult' => false,
                'isToLowerCaseEnabledResult' => true,
                'isUriInBlocklistResult' => true,
                'statusCode' => 301,
                'trailingSlashMode' => TrailingSlashModeEnum::ADD,
            ],
            [
                'originalUrl' => 'https://local.dev/test/test2',
                'expectedUrl' => 'https://local.dev/test/test2/',
                'isTrailingSlashEnabledResult' => false,
                'isToLowerCaseEnabledResult' => true,
                'isUriInBlocklistResult' => false,
                'statusCode' => 301,
                'trailingSlashMode' => TrailingSlashModeEnum::ADD,
            ],
            [
                'originalUrl' => 'https://local.dev/test/test2/',
                'expectedUrl' => 'https://local.dev/test/test2',
                'isTrailingSlashEnabledResult' => true,
                'isToLowerCaseEnabledResult' => false,
                'isUriInBlocklistResult' => false,
                'statusCode' => 301,
                'trailingSlashMode' => TrailingSlashModeEnum::REMOVE,
            ],
            [
                'originalUrl' => 'https://local.dev/missing',
                'expectedUrl' => 'https://local.dev/missing/',
                'isTrailingSlashEnabledResult' => true,
                'isToLowerCaseEnabledResult' => false,
                'isUriInBlocklistResult' => false,
                'statusCode' => 301,
                'trailingSlashMode' => TrailingSlashModeEnum::ADD,
                'handlerStatusCode' => 404,
            ],
            [
                'originalUrl' => 'https://local.dev',
                'expectedUrl' => 'https://local.dev/',
                'isTrailingSlashEnabledResult' => true,
                'isToLowerCaseEnabledResult' => false,
                'isUriInBlocklistResult' => false,
                'statusCode' => 404,
                'trailingSlashMode' => TrailingSlashModeEnum::ADD,
                'expectedException' => HttpException::class
            ],
        ];
    }
}
