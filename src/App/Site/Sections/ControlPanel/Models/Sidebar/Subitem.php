<?php

declare(strict_types=1);

namespace App\Site\Sections\ControlPanel\Models\Sidebar;

readonly class Subitem
{
    public function __construct(
        public string $name,
        public string $path,
    )
    {

    }
}