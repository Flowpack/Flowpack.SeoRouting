<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Tests\Unit\Helper;

use Flowpack\SeoRouting\Helper\BlocklistHelper;
use Flowpack\SeoRouting\Helper\ConfigurationHelper;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(BlocklistHelper::class)]
class BlocklistHelperTest extends TestCase
{
    #[DataProvider('urlDataProvider')]
    public function testIsUriInBlocklist(string $input, bool $expected): void
    {
        $blocklistHelper = new BlocklistHelper();
        $configurationHelperMock = $this->createMock(ConfigurationHelper::class);

        $configurationHelperMock->expects($this->once())->method('getBlocklist')->willReturn(
            ['/neos.*' => false, '.*test.*' => true]
        );

        $reflection = new ReflectionClass($blocklistHelper);
        $property = $reflection->getProperty('configurationHelper');
        $property->setValue($blocklistHelper, $configurationHelperMock);

        $uri = new Uri($input);

        self::assertSame($expected, $blocklistHelper->isUriInBlocklist($uri));
    }

    /**
     * @return array{array{string, bool}}
     */
    public static function urlDataProvider(): array
    {
        return [
            ['https://test.de/neos', false],
            ['https://test.de/neos/test', true],
            ['https://neos.de/foo', false],
        ];
    }
}
