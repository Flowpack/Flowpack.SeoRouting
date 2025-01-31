<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Enum;

enum TrailingSlashModeEnum: string
{
    case ADD = 'add';
    case REMOVE = 'remove';
}
