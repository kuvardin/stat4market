<?php

declare(strict_types=1);

namespace App\Languages;

trait LocaleRequiredTrait
{
    protected string $locale_value;

    public function getLocaleValue(): string
    {
        return $this->locale_value;
    }

    public function getLocale(): Locale
    {
        return Locale::from($this->locale_value);
    }
}