<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Tests\Unit\Helper;

use Flowpack\SeoRouting\Helper\TrailingSlashHelper;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TrailingSlashHelper::class)]
class TrailingSlashHelperTest extends TestCase
{
    #[DataProvider('urlDataProvider')]
    public function testAppendTrailingSlash(string $input, string $output): void
    {
        $uri = new Uri($input);

        self::assertSame($output, (string)(new TrailingSlashHelper())->appendTrailingSlash($uri));
    }

    /**
     * @return array{string[]}
     */
    public static function urlDataProvider(): array
    {
        return [
            ['', ''],
            ['/', '/'],
            ['/foo', '/foo/'],
            ['/foo/bar', '/foo/bar/'],
            ['https://test.de', 'https://test.de'],
            ['https://test.de/', 'https://test.de/'],
            ['https://test.de/foo/bar', 'https://test.de/foo/bar/'],
            ['https://test.de/foo/bar/', 'https://test.de/foo/bar/'],
            ['/foo/bar?some-query=foo%20bar', '/foo/bar/?some-query=foo%20bar'],
            ['/foo/bar#some-fragment', '/foo/bar/#some-fragment'],
            ['/foo/bar?some-query=foo%20bar#some-fragment', '/foo/bar/?some-query=foo%20bar#some-fragment'],
            [
                'https://test.de/foo/bar?some-query=foo%20bar#some-fragment',
                'https://test.de/foo/bar/?some-query=foo%20bar#some-fragment',
            ],
            ['mailto:some.email@foo.bar', 'mailto:some.email@foo.bar'],
            ['tel:+4906516564', 'tel:+4906516564'],
            ['https://test.de/foo/bar.css', 'https://test.de/foo/bar.css'],
        ];
    }
}
