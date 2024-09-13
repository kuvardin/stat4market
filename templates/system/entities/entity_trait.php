<?php

declare(strict_types=1);

/**
 * @var string $entity_name
 * @var Kuvardin\TinyOrm\Table $table
 * @var App\Postgresql\ColumnInfo[] $columns
 * @var string[] $namespace
 * @var string $field_name_sc
 * @var string $field_name_cc
 */

echo '<?php';
?>


declare(strict_types=1);

<?php if ($namespace !== []): ?>
namespace <?= implode('\\', $namespace) ?>;
<?php endif; ?>

trait <?= $entity_name ?>Trait
{
    protected ?int $<?= $field_name_sc ?>_id;

    public function get<?= $field_name_cc ?>Id(): ?int
    {
        return $this-><?= $field_name_sc ?>_id;
    }

    public function get<?= $field_name_cc ?>(): ?<?= $entity_name ?>

    {
        return $this-><?= $field_name_sc ?>_id === null
            ? null
            : <?= $entity_name ?>::requireOneById($this-><?= $field_name_sc ?>_id);
    }
}
