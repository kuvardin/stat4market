<?php

declare(strict_types=1);

namespace App\Site\Sections\ControlPanel\Models;

readonly class Breadcrumb
{
    public function __construct(
        public string $name,
        public string $path,
        public ?array $get = null,
    )
    {

    }
}