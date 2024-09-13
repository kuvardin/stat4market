<?php

declare(strict_types=1);

namespace App\Languages;

use App;
use App\Interfaces\CreatableInterface;
use Kuvardin\TinyOrm\Conditions\ConditionExpression;
use Kuvardin\TinyOrm\Connection;
use Kuvardin\TinyOrm\EntityAbstract;
use Kuvardin\TinyOrm\Grouping\GroupingSimple;
use Kuvardin\TinyOrm\Sorting\Sorting;
use Kuvardin\TinyOrm\Sorting\SortingSettings;
use Kuvardin\TinyOrm\Table;
use PDO;
use RuntimeException;

class TranslationsFactory
{
    /**
     * @var array<int,TranslationsCollection>
     */
    protected array $not_received = [];

    /**
     * @var array<int,TranslationsCollection>
     */
    protected array $received = [];

    /**
     * @param class-string $translatable_entity_class
     * @param string[] $translatable_columns
     */
    public function __construct(
        public readonly Connection $connection,
        public readonly string $translatable_entity_class,
        public readonly Table $translations_table,
        public readonly array $translatable_columns,
    )
    {

    }

    public function receiveTranslations(): void
    {
        if ($this->not_received === []) {
            return;
        }

        $stmt = App::pdo()
            ->getQueryBuilder()
            ->createSelectQuery($this->translations_table)
            ->andWhere(
                new ConditionExpression(
                    App::pdo()->expr()->in(
                        $this->translations_table->getColumn(TranslatableInterface::COL_TRANSLATABLE_ID),
                        array_keys($this->not_received),
                    ),
                ),
            )
            ->appendGroupingElement(
                new GroupingSimple([
                    $this->translations_table->getColumn(EntityAbstract::COL_ID),
                    $this->translations_table->getColumn(TranslatableInterface::COL_TRANSLATABLE_ID),
                    $this->translations_table->getColumn(TranslatableInterface::COL_LOCALE),
                ]),
            )
            ->setSortingSettings(
                new SortingSettings([
                    Sorting::desc(
                        $this->translations_table->getColumn(CreatableInterface::COL_CREATION_DATE),
                    ),
                ]),
            )
            ->execute()
        ;

        foreach ($this->not_received as $translatable_id => $translations_collection) {
            $this->received[$translatable_id] = $translations_collection;
        }


        $received_ids = [];

        while ($translation_data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $translatable_id = $translation_data[TranslatableInterface::COL_TRANSLATABLE_ID];
            $received_ids[] = $translatable_id;
            $locale = Locale::from($translation_data[TranslatableInterface::COL_LOCALE]);
            $this->received[$translatable_id]->setTranslation($locale, $translation_data);
        }

        $not_received_ids = array_diff(array_keys($this->not_received), $received_ids);

//        print_r([
//            'nr' => array_keys($this->not_received),
//            'r' => $received_ids,
//            'diff' => $not_received_ids,
//        ]);
        foreach ($not_received_ids as $not_received_id) {
            $this->not_received[$not_received_id]->setNotFound();
        }

        $this->not_received = [];
    }

    public function getTranslations(TranslatableInterface|int $translatable): TranslationsCollection
    {
        if ($translatable instanceof TranslatableInterface) {
            if (get_class($translatable) !== $this->translatable_entity_class) {
                throw new RuntimeException(sprintf(
                    'Wrong translatable class: %s (must be %s)',
                    get_class($translatable),
                    $this->translatable_entity_class,
                ));
            }

            $translatable_id = $translatable->getId();
        } else {
            $translatable_id = $translatable;
        }

        return $this->received[$translatable_id] ?? $this->not_received[$translatable_id]
            ??= new TranslationsCollection($this, $translatable_id);
    }
}