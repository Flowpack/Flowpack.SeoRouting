<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Tests\Unit\Helper;

use Flowpack\SeoRouting\Enum\TrailingSlashModeEnum;
use Flowpack\SeoRouting\Helper\ConfigurationHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(ConfigurationHelper::class)]
class ConfigurationHelperTest extends TestCase
{
    private readonly ConfigurationHelper $configurationHelper;
    /** @var ReflectionClass<ConfigurationHelper> */
    private readonly ReflectionClass $configurationHelperReflection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurationHelper = new ConfigurationHelper();
        $this->configurationHelperReflection = new ReflectionClass($this->configurationHelper);
    }

    public function testIsTrailingSlashEnabledShouldReturnTrue(): void
    {
        $this->injectConfiguration(['enable' => ['trailingSlash' => true, 'toLowerCase' => false]]);

        self::assertTrue($this->configurationHelper->isTrailingSlashEnabled());
    }

    public function testIsTrailingSlashEnabledShouldReturnFalse(): void
    {
        $this->injectConfiguration(['enable' => ['trailingSlash' => false, 'toLowerCase' => true]]);

        self::assertFalse($this->configurationHelper->isTrailingSlashEnabled());
    }

    public function testGetTrailingSlashModeShouldReturnGivenMode(): void
    {
        $this->injectConfiguration(['trailingSlashMode' => 'remove']);

        self::assertSame(TrailingSlashModeEnum::REMOVE, $this->configurationHelper->getTrailingSlashMode());
    }

    public function testGetTrailingSlashModeShouldReturnDefaultMode(): void
    {
        $this->injectConfiguration(['trailingSlashMode' => 'foo']);

        self::assertSame(TrailingSlashModeEnum::ADD, $this->configurationHelper->getTrailingSlashMode());
    }

    public function testIsToLowerCaseEnabledShouldReturnTrue(): void
    {
        $this->injectConfiguration(['enable' => ['trailingSlash' => false, 'toLowerCase' => true]]);

        self::assertTrue($this->configurationHelper->isToLowerCaseEnabled());
    }

    public function testIsToLowerCaseEnabledShouldReturnFalse(): void
    {
        $this->injectConfiguration(['enable' => ['trailingSlash' => true, 'toLowerCase' => false]]);

        self::assertFalse($this->configurationHelper->isToLowerCaseEnabled());
    }

    public function testGetBlocklist(): void
    {
        $property = $this->configurationHelperReflection->getProperty('blocklist');
        $property->setValue($this->configurationHelper, ['/neos.*' => false,]);

        self::assertEquals(['/neos.*' => false,], $this->configurationHelper->getBlocklist());
    }

    public function testGetStatusCodeShouldReturnDefaultValue(): void
    {
        $this->injectConfiguration([]);

        self::assertSame(301, $this->configurationHelper->getStatusCode());
    }

    public function testGetStatusCodeShouldReturnConfiguredValue(): void
    {
        $this->injectConfiguration(['enable' => ['trailingSlash' => true, 'toLowerCase' => false], 'statusCode' => 302]
        );

        self::assertSame(302, $this->configurationHelper->getStatusCode());
    }

    /**
     * @param  array{enable?: array{trailingSlash: bool, toLowerCase: bool}, statusCode?: int, trailingSlashMode?: string}|array{}  $configuration
     */
    private function injectConfiguration(array $configuration): void
    {
        $property = $this->configurationHelperReflection->getProperty('configuration');
        $property->setValue($this->configurationHelper, $configuration);
    }
}
