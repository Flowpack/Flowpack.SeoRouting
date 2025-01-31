<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Tests\Unit;

use Flowpack\SeoRouting\Enum\TrailingSlashModeEnum;
use Flowpack\SeoRouting\Helper\BlocklistHelper;
use Flowpack\SeoRouting\Helper\ConfigurationHelper;
use Flowpack\SeoRouting\Helper\TrailingSlashHelper;
use Flowpack\SeoRouting\LinkingServiceAspect;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Aop\Advice\AdviceChain;
use Neos\Flow\Aop\JoinPointInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function PHPUnit\Framework\assertSame;

#[CoversClass(LinkingServiceAspect::class)]
class LinkingServiceAspectTest extends TestCase
{
    private readonly LinkingServiceAspect $linkingServiceAspect;
    private readonly JoinPointInterface&MockObject $joinPointMock;
    private readonly AdviceChain&MockObject $adviceChainMock;
    private readonly TrailingSlashHelper&MockObject $trailingSlashHelperMock;
    private readonly ConfigurationHelper&MockObject $configurationHelperMock;
    private readonly BlocklistHelper&MockObject $blocklistHelperMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->linkingServiceAspect = new LinkingServiceAspect();

        $this->joinPointMock = $this->createMock(JoinPointInterface::class);
        $this->adviceChainMock = $this->createMock(AdviceChain::class);
        $this->trailingSlashHelperMock = $this->createMock(TrailingSlashHelper::class);
        $this->configurationHelperMock = $this->createMock(ConfigurationHelper::class);
        $this->blocklistHelperMock = $this->createMock(BlocklistHelper::class);

        $reflection = new ReflectionClass($this->linkingServiceAspect);
        $reflectionProperty = $reflection->getProperty('trailingSlashHelper');
        $reflectionProperty->setValue($this->linkingServiceAspect, $this->trailingSlashHelperMock);
        $reflectionProperty = $reflection->getProperty('configurationHelper');
        $reflectionProperty->setValue($this->linkingServiceAspect, $this->configurationHelperMock);
        $reflectionProperty = $reflection->getProperty('blocklistHelper');
        $reflectionProperty->setValue($this->linkingServiceAspect, $this->blocklistHelperMock);

        $this->joinPointMock->expects($this->once())->method('getAdviceChain')->willReturn($this->adviceChainMock);
    }

    public function testAppendTrailingSlashToNodeUriShouldNotChangeResultIfTrailingSlashIsDisabled(): void
    {
        $result = 'foo';

        $this->configurationHelperMock->expects($this->once())->method('isTrailingSlashEnabled')->willReturn(false);

        $this->adviceChainMock->expects($this->once())->method('proceed')->willReturn($result);

        assertSame(
            $result,
            $this->linkingServiceAspect->handleTrailingSlashForNodeUri($this->joinPointMock)
        );
    }

    public function testAppendTrailingSlashToNodeUriShouldNotChangeResultIfUriIsMalformed(): void
    {
        $result = 'https://';
        $this->adviceChainMock->expects($this->once())->method('proceed')->willReturn($result);

        $this->configurationHelperMock->expects($this->once())->method('isTrailingSlashEnabled')->willReturn(true);

        assertSame(
            $result,
            $this->linkingServiceAspect->handleTrailingSlashForNodeUri($this->joinPointMock)
        );
    }

    public function testAppendTrailingSlashToNodeUriShouldNotChangeResultIfUriIsInBlocklist(): void
    {
        $result = 'foo';
        $this->adviceChainMock->expects($this->once())->method('proceed')->willReturn($result);

        $this->configurationHelperMock->expects($this->once())->method('isTrailingSlashEnabled')->willReturn(true);
        $this->blocklistHelperMock->expects($this->once())->method('isUriInBlocklist')->willReturn(true);


        assertSame(
            $result,
            $this->linkingServiceAspect->handleTrailingSlashForNodeUri($this->joinPointMock)
        );
    }

    public function testAppendTrailingSlashToNodeUriShouldAppendTrailingSlash(): void
    {
        $result = 'foo/';
        $this->adviceChainMock->expects($this->once())->method('proceed')->willReturn('foo');

        $this->configurationHelperMock->expects($this->once())->method('isTrailingSlashEnabled')->willReturn(true);
        $this->configurationHelperMock->expects($this->once())->method('getTrailingSlashMode')->willReturn(
            TrailingSlashModeEnum::ADD
        );
        $this->blocklistHelperMock->expects($this->once())->method('isUriInBlocklist')->willReturn(false);
        $this->trailingSlashHelperMock->expects($this->once())->method('appendTrailingSlash')->willReturn(
            new Uri($result)
        );

        assertSame(
            $result,
            $this->linkingServiceAspect->handleTrailingSlashForNodeUri($this->joinPointMock)
        );
    }

    public function testAppendTrailingSlashToNodeUriShouldRemoveTrailingSlash(): void
    {
        $result = 'foo/';
        $this->adviceChainMock->expects($this->once())->method('proceed')->willReturn('foo');

        $this->configurationHelperMock->expects($this->once())->method('isTrailingSlashEnabled')->willReturn(true);
        $this->configurationHelperMock->expects($this->once())->method('getTrailingSlashMode')->willReturn(
            TrailingSlashModeEnum::REMOVE
        );
        $this->blocklistHelperMock->expects($this->once())->method('isUriInBlocklist')->willReturn(false);
        $this->trailingSlashHelperMock->expects($this->once())->method('removeTrailingSlash')->willReturn(
            new Uri($result)
        );

        assertSame(
            $result,
            $this->linkingServiceAspect->handleTrailingSlashForNodeUri($this->joinPointMock)
        );
    }
}
