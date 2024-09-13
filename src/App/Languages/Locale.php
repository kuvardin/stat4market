<?php

declare(strict_types=1);

namespace App\Languages;

use RuntimeException;
use Libs\Krisha\Enums\Locale as KrishaLocale;

enum Locale: string
{
    case RU = 'ru';
    case KK = 'kk';
    case UZ = 'uz';
    case EN = 'en';
    case ID = 'id';

    public function getName(): string
    {
        return match ($this) {
            self::RU => 'Русский язык',
            self::KK => 'Қазақ тілі',
            self::UZ => 'О\ʻzbek tili',
            default => throw new RuntimeException("Unknown locale: {$this->name}"),
        };
    }

    public function getEnglishName(): string
    {
        return match ($this) {
            self::RU => 'Russian',
            self::KK => 'Kazakh',
            self::UZ => 'Uzbek',
            default => throw new RuntimeException("Unknown locale: {$this->name}"),
        };
    }

    public function getEmoji(): string
    {
        return match ($this) {
            self::RU => "\xee\x94\x92",
            self::KK => "\xf0\x9f\x87\xb0\xf0\x9f\x87\xbf",
            self::UZ => "\u{1F1FA}\u{1F1FF}",
            default => throw new RuntimeException("Unknown locale: {$this->name}"),
        };
    }

    public function getPhraseInCurrentLanguage(): string
    {
        return match ($this) {
            self::RU => 'На русском',
            self::KK => 'Қазақша',
            self::UZ => 'O\'zbek tilida',
            default => throw new RuntimeException("Unknown locale: {$this->name}"),
        };
    }

    public static function fromKrishaLocale(KrishaLocale $krisha_locale): ?self
    {
        return match ($krisha_locale) {
            KrishaLocale::Russian => self::RU,
            KrishaLocale::Kazakh => self::KK,
            default => null,
        };
    }
}
