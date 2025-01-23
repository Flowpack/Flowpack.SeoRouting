<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Tests\Unit\Helper;

use Flowpack\SeoRouting\Helper\LowerCaseHelper;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LowerCaseHelper::class)]
class LowerCaseHelperTest extends TestCase
{
    #[DataProvider('urlDataProvider')]
    public function testConvertPathToLowerCase(string $input, string $output): void
    {
        $uri = new Uri($input);

        self::assertSame($output, (string)(new LowerCaseHelper())->convertPathToLowerCase($uri));
    }

    /**
     * @return array{string[]}
     */
    public static function urlDataProvider(): array
    {
        return [
            ['', ''],
            ['/', '/'],
            ['/foo', '/foo'],
            ['/foo/bar', '/foo/bar'],
            ['https://test.de', 'https://test.de'],
            ['https://test.de/', 'https://test.de/'],
            ['https://test.de/FOO/bar', 'https://test.de/foo/bar'],
            ['https://test.de/foo/bar/', 'https://test.de/foo/bar/'],
            ['/foo/bar?some-QUERY=foo%20bar', '/foo/bar?some-QUERY=foo%20bar'],
            ['/foo/bar#some-fragment', '/foo/bar#some-fragment'],
            ['/foo/bar?some-query=foo%20bar#some-FRAGMENT', '/foo/bar?some-query=foo%20bar#some-FRAGMENT'],
            [
                'https://test.de/FOO/bar?some-query=foo%20bar#SOME-fragment',
                'https://test.de/foo/bar?some-query=foo%20bar#SOME-fragment',
            ],
            ['mailto:some.email@FOO.bar', 'mailto:some.email@FOO.bar'],
            ['tel:+4906516564', 'tel:+4906516564'],
            ['https://test.de/foo/BAR.css', 'https://test.de/foo/BAR.css'],
        ];
    }
}
