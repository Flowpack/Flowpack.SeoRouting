<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Exceptions\Http;

use Throwable;

/**
 * An exception where the HTTP status code can be set.
 */
class Exception extends \Neos\Flow\Http\Exception
{
    public function __construct(int $statusCode, string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->statusCode = $statusCode;
    }
}
