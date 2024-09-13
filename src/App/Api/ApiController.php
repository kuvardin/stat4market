<?php

declare(strict_types=1);

namespace App\Api;

use App\Web\WebRequest;
use RuntimeException;

class ApiController
{
    private function __construct()
    {
    }

    public static function handle(
        WebRequest $request,
    ): void
    {
        $route_parts = explode('/', trim($request->route, '/'));

        $route_part_first = array_shift($route_parts);
        if ($route_part_first !== 'api') {
            throw new RuntimeException("Incorrect API route: {$request->route}");
        }

        $version = array_shift($route_parts);
        if ($version === null || $version === '') {
            http_response_code(404);
            return;
        }

        $version_controller_class = self::getVersionControllerClass($version);
        if (!class_exists($version_controller_class)) {
            http_response_code(404);
            return;
        }

        $version_controller_class::handle($request, $route_parts);
    }

    private static function getVersionControllerClass(
        string $version,
    ): string|ApiVersionController
    {
        return "App\\Api\\$version\\ApiVersionController";
    }
}