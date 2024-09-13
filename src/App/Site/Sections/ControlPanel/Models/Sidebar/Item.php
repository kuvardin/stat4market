<?php

declare(strict_types=1);

namespace App\Site\Sections\ControlPanel\Models\Sidebar;

use RuntimeException;

readonly class Item
{
    /**
     * @param Subitem[] $subitems
     */
    private function __construct(
        public string $name,
        public string $icon,
        public array $subitems,
        public ?string $path,
        public ?int $counter = null,
    )
    {
        if (!(($this->subitems === []) xor empty($this->path))) {
            throw new RuntimeException('Sidebar item must have only subitems or only path');
        }
    }

    /**
     * @param Subitem[] $subitems
     */
    public static function withSubitems(string $name, string $icon, array $subitems, int $counter = null): self
    {
        return new self($name, $icon, $subitems, null, $counter);
    }

    public static function withPath(string $name, string $icon, string $path, int $counter = null): self
    {
        return new self($name, $icon, [], $path, $counter);
    }
}