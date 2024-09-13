<?php

declare(strict_types=1);

namespace App\Languages;

use Kuvardin\TinyOrm\Table;

interface TranslatableInterface
{
    public const string COL_TRANSLATABLE_ID = 'translatable_id';
    public const string COL_LOCALE = 'locale';

    public static function getEntityTranslationsTableDefault(): Table;

    public static function getTranslatableColumnsNames(): array;

    public static function removeTranslationFactory(?Table $translation_table): void;

    public function getTranslations(Table $translations_table = null): TranslationsCollection;

    public function initTranslatable(Table $translations_table = null): void;
}