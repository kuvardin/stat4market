<?php

declare(strict_types=1);

namespace App\Site\Sections\ControlPanel\Models;

use App\Enums\BootstrapColor;

readonly class Alert
{
    public function __construct(
        public string $text,
        public ?BootstrapColor $bootstrap_color,
        public ?bool $filter_text = true,
    )
    {

    }
}