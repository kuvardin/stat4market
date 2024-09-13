<?php

declare(strict_types=1);

namespace App\Languages;

use App;
use App\Interfaces\CreatableInterface;
use Kuvardin\TinyOrm\Connection;
use Kuvardin\TinyOrm\Table;
use RuntimeException;

class TranslationsCollection
{
    /**
     * @var array<string,Phrase>|null
     */
    protected ?array $phrases = null;

    public function __construct(
        protected TranslationsFactory $factory,
        protected int $translatable_id,
    )
    {

    }

    /**
     * @param array<string,Phrase> $phrases
     */
    public static function create(
        Table $translations_table,
        int $translatable_id,
        array $phrases,
        Connection $connection = null,
        int $creation_date = null,
    ): void
    {
        $connection ??= App::pdo();

        $data = [];

        foreach ($phrases as $column_name => $phrase) {
            if ($phrase === null) {
                continue;
            }

            foreach ($phrase->toArray() as $locale_value => $phrase_value) {
                if (array_key_exists($locale_value, $data)) {
                    $data[$locale_value][$column_name] = $phrase_value;
                } else {
                    $data[$locale_value] = [
                        $column_name => $phrase_value,
                    ];
                }
            }
        }

        if ($data === []) {
            return;
        }

        $creation_date ??= time();

        foreach ($data as $locale_value => $translations_data) {
            $translations_data[CreatableInterface::COL_CREATION_DATE] = $creation_date;
            $translations_data[TranslatableInterface::COL_TRANSLATABLE_ID] = $translatable_id;
            $translations_data[TranslatableInterface::COL_LOCALE] = $locale_value;

            $connection
                ->qb()
                ->createInsertQuery($translations_table)
                ->addValuesSetFromArray($translations_data)
                ->execute();
        }
    }

    public function getPhrase(string $name): ?Phrase
    {
        if ($this->phrases === null) {
            $this->factory->receiveTranslations();
        }

        if ($this->phrases === null) {
            throw new RuntimeException("Phrases for {$this->factory->translations_table} {$this->translatable_id} not found");
        }

        if (!in_array($name, $this->factory->translatable_columns)) {
            throw new RuntimeException("Phrase $name not found in {$this->factory->translations_table}");
        }

        return array_key_exists($name, $this->phrases) ? $this->phrases[$name] : null;
    }

    public function setNotFound(): void
    {
        if ($this->phrases === null) {
            $this->phrases = [];
        }
    }

    public function setTranslation(Locale $locale, array $data): self
    {
        $translatable_id = $data[TranslatableInterface::COL_TRANSLATABLE_ID];
        if ($translatable_id !== $this->translatable_id) {
            throw new RuntimeException(sprintf(
                'Wrong translatable ID: %s (must be %s)',
                $translatable_id,
                $this->translatable_id,
            ));
        }

        if ($this->phrases === null) {
            $this->phrases = [];
        }

        foreach ($this->factory->translatable_columns as $translatable_column) {
            if (!array_key_exists($translatable_column, $data)) {
                throw new RuntimeException("Phrase $translatable_column not found in translation data");
            }

            if ($data[$translatable_column] !== null) {
                if (!is_string($data[$translatable_column])) {
                    throw new RuntimeException(
                        'Wrong translatable column type: ' . gettype($data[$translatable_column]),
                    );
                }

                if (empty($this->phrases[$translatable_column])) {
                    $this->phrases[$translatable_column] = new Phrase([
                        $locale->value => $data[$translatable_column],
                    ]);
                } else {
                    $this->phrases[$translatable_column]->setValue($locale, $data[$translatable_column]);
                }
            }
        }

        return $this;
    }
}