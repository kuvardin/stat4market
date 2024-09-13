<?php

declare(strict_types=1);

namespace App\Site\Sections\ControlPanel\Models\Sidebar;

/**
 * Блок ссылок сайдбара
 */
readonly class Block
{
    /**
     * @param Item[] $items
     */
    public function __construct(
        public string $name,
        public array $items,
    )
    {

    }
}