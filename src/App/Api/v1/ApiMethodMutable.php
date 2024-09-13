<?php

declare(strict_types=1);

namespace App\Api\v1;

use App\Api\v1\Exceptions\ApiException;
use App\Api\v1\Input\ApiInput;
use App\Sessions\Session;

abstract class ApiMethodMutable extends ApiMethod
{
    final public static function isMutable(): bool
    {
        return true;
    }

    /**
     * @throws ApiException
     */
    abstract public static function handle(ApiInput $input, Session $session);
}
