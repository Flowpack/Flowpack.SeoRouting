<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Helper;

use Flowpack\SeoRouting\Enum\TrailingSlashModeEnum;
use Neos\Flow\Annotations as Flow;

#[Flow\Scope('singleton')]
class ConfigurationHelper
{
    /** @var array{enable: array{trailingSlash: bool, toLowerCase: bool}, statusCode?: int, trailingSlashMode: string} */
    #[Flow\InjectConfiguration(path: 'redirect')]
    protected array $configuration;

    /** @var array{string: bool} */
    #[Flow\InjectConfiguration(path: 'blocklist')]
    protected array $blocklist;

    public function isTrailingSlashEnabled(): bool
    {
        return $this->configuration['enable']['trailingSlash'] ?? false;
    }

    public function getTrailingSlashMode(): TrailingSlashModeEnum
    {
        return TrailingSlashModeEnum::tryFrom($this->configuration['trailingSlashMode']) ?? TrailingSlashModeEnum::ADD;
    }

    public function isToLowerCaseEnabled(): bool
    {
        return $this->configuration['enable']['toLowerCase'] ?? false;
    }

    public function getStatusCode(): int
    {
        return $this->configuration['statusCode'] ?? 301;
    }

    /**
     * @return array{string: bool}
     */
    public function getBlocklist(): array
    {
        return $this->blocklist;
    }
}
