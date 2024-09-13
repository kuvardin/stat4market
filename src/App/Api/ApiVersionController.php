<?php

declare(strict_types=1);

namespace App\Api;

use App\Web\WebRequest;

abstract class ApiVersionController
{
    private function __construct()
    {
    }

    abstract public static function handle(
        WebRequest $request,
        array $route_parts,
    ): void;
}