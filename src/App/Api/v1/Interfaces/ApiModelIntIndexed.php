<?php

declare(strict_types=1);

namespace App\Api\v1\Interfaces;

interface ApiModelIntIndexed
{
    public function getIndex(): int;
}