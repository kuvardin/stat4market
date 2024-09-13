<?php

declare(strict_types=1);

namespace App\Site\Sections\ControlPanel\Models;

use App\Enums\BootstrapColor;

readonly class Link
{
    public function __construct(
        public string $title,
        public string $path,
        public ?BootstrapColor $bootstrap_color = null,
        public ?int $counter = null,
        public ?string $icon = null,
    )
    {

    }
}