<?php

declare(strict_types=1);

namespace App\Languages;

use Kuvardin\TinyOrm\EntityAbstract;
use Kuvardin\TinyOrm\Table;

/**
 * @mixin EntityAbstract
 * @mixin TranslatableInterface
 */
trait TranslatableTrait
{
    /**
     * @var TranslationsFactory[]
     */
    protected static array $translations_factories = [];

    public static function removeTranslationFactory(?Table $translation_table): void
    {
        if ($translation_table === null) {
            self::$translations_factories = [];
        } else {
            $translation_table_full_name = $translation_table->getFullName();
            if (array_key_exists($translation_table_full_name, self::$translations_factories)) {
                unset(self::$translations_factories[$translation_table_full_name]);
            }
        }
    }

    public function initTranslatable(Table $translations_table = null): void
    {
        $this->getTranslations($translations_table);
    }

    public function getTranslations(Table $translations_table = null): TranslationsCollection
    {
        $translations_table ??= static::getEntityTranslationsTableDefault();
        $translations_table_full_name = $translations_table->getFullName();

        static::$translations_factories[$translations_table_full_name] ??= new TranslationsFactory(
            connection: $this->getConnection(),
            translatable_entity_class: static::class,
            translations_table: $translations_table,
            translatable_columns: static::getTranslatableColumnsNames(),
        );

        return static::$translations_factories[$translations_table_full_name]->getTranslations($this);
    }
}