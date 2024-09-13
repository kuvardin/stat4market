<?php

declare(strict_types=1);

namespace App\Api\v1;

use App\Sessions\Session;

abstract class ApiModelMutable extends ApiModel
{
    final public static function isMutable(): bool
    {
        return true;
    }

    abstract public function getPublicData(Session $session): array;
}
