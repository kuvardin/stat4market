<?php

declare(strict_types=1);

/**
 * @var string $entity_name
 * @var Kuvardin\TinyOrm\Table $table
 * @var App\Postgresql\ColumnInfo[] $columns
 * @var string[] $namespace
 */

use App\Interfaces\CreatableInterface;

echo '<?php';
?>


declare(strict_types=1);

<?php if ($namespace !== []): ?>
namespace <?= implode('\\', $namespace) ?>;
<?php endif; ?>

use App;
use App\Interfaces\CreatableInterface;
use Kuvardin\TinyOrm\EntityAbstract;
use App\Traits\CreationDateRequiredTrait;
use Kuvardin\TinyOrm\Connection;
use Kuvardin\TinyOrm\Table;
use Kuvardin\TinyOrm\Exception\AlreadyExists;

class <?= $entity_name ?> extends EntityAbstract
{
    use CreationDateRequiredTrait;

<?php foreach ($columns as $column): ?>
<?php if ($column->name !== CreatableInterface::COL_CREATION_DATE): ?>
    public const string COL_<?= strtoupper($column->name) ?> = '<?= $column->name ?>';
<?php endif; ?>
<?php endforeach; ?>

<?php foreach ($columns as $column): ?>
<?php if ($column->name !== CreatableInterface::COL_CREATION_DATE): ?>
    protected <?= $column->getPhpTypeFull() ?> $<?= $column->name ?>;
<?php endif; ?>
<?php endforeach; ?>

    public function __construct(Connection $pdo, Table $table, array $data)
    {
        parent::__construct($pdo, $table, $data);
<?php foreach ($columns as $column): ?>
<?php if ($column->name === CreatableInterface::COL_CREATION_DATE): ?>
        $this-><?= $column->name ?> = $data[CreatableInterface::COL_CREATION_DATE];
<?php else: ?>
        $this-><?= $column->name ?> = $data[self::COL_<?= strtoupper($column->name) ?>];
<?php endif; ?>
<?php endforeach; ?>
    }

    public static function getEntityTableDefault(): Table
    {
<?php if ($table->schema === null || $table->schema === 'public'): ?>
        return new Table('<?= $table->name ?>');
<?php else: ?>
        return new Table('<?= $table->name ?>', '<?= $table->schema ?>');
<?php endif; ?>
    }

    /**
     * @throws AlreadyExists
     */
    public static function create(
<?php foreach ($columns as $column): ?>
<?php if (!$column->is_nullable && $column->name !== CreatableInterface::COL_CREATION_DATE): ?>
        <?= $column->getPhpType() ?> $<?= $column->name ?>,
<?php endif; ?>
<?php endforeach; ?>
<?php foreach ($columns as $column): ?>
<?php if ($column->is_nullable || $column->name === CreatableInterface::COL_CREATION_DATE): ?>
        <?= $column->getPhpType() ?> $<?= $column->name ?> = null,
<?php endif; ?>
<?php endforeach; ?>
    ): self
    {
        return self::createByValuesSet([
<?php foreach ($columns as $column): ?>
<?php if ($column->name === CreatableInterface::COL_CREATION_DATE): ?>
            CreatableInterface::COL_CREATION_DATE => $creation_date ?? time(),
<?php else: ?>
            self::COL_<?= strtoupper($column->name) ?> => $<?= $column->name ?>,
<?php endif; ?>
<?php endforeach; ?>
        ]);
    }
<?php foreach ($columns as $column): ?>
<?php if ($column->name === CreatableInterface::COL_CREATION_DATE) continue; ?>

    public function get<?= $column->getNameInCamelCase() ?>(): <?= $column->getPhpTypeFull() ?>

    {
        return $this-><?= $column->name ?>;
    }

    public function set<?= $column->getNameInCamelCase() ?>(<?= $column->getPhpTypeFull() ?> $<?= $column->name ?>): self
    {
        $this->setFieldValue(self::COL_<?= strtoupper($column->name) ?>, $this-><?= $column->name ?>, $<?= $column->name ?>);
        return $this;
    }
<?php endforeach; ?>
}
