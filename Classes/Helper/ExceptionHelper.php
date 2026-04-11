<?php

declare(strict_types=1);

namespace Flowpack\SeoRouting\Helper;

use Neos\Flow\Http\Exception;

class ExceptionHelper extends Exception
{
    /**
     * @param int $statusCode
     * @return void
     */
    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }
}
