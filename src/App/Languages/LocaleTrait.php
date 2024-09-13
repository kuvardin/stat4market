<?php

declare(strict_types=1);

namespace App\Languages;

trait LocaleTrait
{
    protected ?string $locale_value;

    public function getLocaleValue(): ?string
    {
        return $this->locale_value;
    }

    public function getLocale(): ?Locale
    {
        return $this->locale_value === null
            ? null
            : Locale::from($this->locale_value);
    }
}