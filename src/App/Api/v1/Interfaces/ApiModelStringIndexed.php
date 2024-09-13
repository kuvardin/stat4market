<?php

declare(strict_types=1);

namespace App\Api\v1\Interfaces;

interface ApiModelStringIndexed
{
    public function getIndex(): string;
}