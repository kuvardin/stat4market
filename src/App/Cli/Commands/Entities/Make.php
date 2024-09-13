<?php

declare(strict_types=1);

namespace App\Cli\Commands\Entities;

use App;
use App\Cli\CliCommand;
use App\Cli\CliExitCode;
use App\Cli\Input\CliInput;
use App\Cli\Output\CliOutput;
use App\Postgresql\ColumnInfo;
use App\TemplatesEngine\TemplatesEngine;
use App\Utils\CaseSwitcher;
use Kuvardin\TinyOrm\Conditions\Condition;
use Kuvardin\TinyOrm\Table;

class Make extends CliCommand
{
    public static function requirePdoConnection(): bool
    {
        return true;
    }

    public static function execute(CliInput $input): int
    {
        $entity_path = $input->getArgument(0);
        $table_schema = $input->getArgument(1);
        $table_name = $input->getArgument(2);
        $entity_path_parts = empty($entity_path) ? [] : explode('/', trim($entity_path, '/'));

        if ($entity_path_parts === [] || empty($table_schema) || empty($table_name)) {
            CliOutput::error('Wrong input');
            return CliExitCode::IOERR;
        }

        $entity_name = array_pop($entity_path_parts);

        $table = new Table($table_name, $table_schema);
        $columns_data = App::pdo()
            ->getQueryBuilder()
            ->createSelectQuery(new Table('columns', 'information_schema'))
            ->setWhere(new Condition('table_name', $table->name))
            ->andWhere(new Condition('table_schema', $table->schema))
            ->execute()
            ->fetchAll()
        ;

        if ($columns_data === []) {
            CliOutput::error("Columns not found for table $table");
            return CliExitCode::DATAERR;
        }

        $columns_filtered = array_filter($columns_data, static fn(array $col) => $col['column_name'] !== 'id');
        $columns = array_map(static fn(array $col) => new ColumnInfo($col), $columns_filtered);

        $dir = rtrim(CLASSES_DIR . '/' . implode('/', $entity_path_parts), '/');

        self::writeFile(
            file_path: "$dir/$entity_name.php",
            content: TemplatesEngine::render('system/entities/entity_class', [
                'entity_name' => $entity_name,
                'table' => $table,
                'columns' => $columns,
                'namespace' => $entity_path_parts,
            ]),
        );

        $namespace_parts = $entity_path_parts;
        if ($namespace_parts[0] === 'App') {
            array_shift($namespace_parts);
        }

        $namespace_last_part = array_pop($namespace_parts);
        if (
            $namespace_last_part !== null
            && $namespace_last_part !== "{$entity_name}s"
            && $namespace_last_part !== "{$entity_name}es"
        ) {
            $namespace_parts[] = $namespace_last_part;
        }

        $field_name_cc = implode('', $namespace_parts) . $entity_name;
        $field_name_sc = CaseSwitcher::camelToSnake($field_name_cc);

        self::writeFile(
            file_path: "$dir/{$entity_name}Trait.php",
            content: TemplatesEngine::render('system/entities/entity_trait', [
                'entity_name' => $entity_name,
                'table' => $table,
                'columns' => $columns,
                'namespace' => $entity_path_parts,
                'field_name_cc' => $field_name_cc,
                'field_name_sc' => $field_name_sc,
            ]),
        );

        self::writeFile(
            file_path: "$dir/{$entity_name}RequiredTrait.php",
            content: TemplatesEngine::render('system/entities/entity_required_trait', [
                'entity_name' => $entity_name,
                'table' => $table,
                'columns' => $columns,
                'namespace' => $entity_path_parts,
                'field_name_cc' => $field_name_cc,
                'field_name_sc' => $field_name_sc,
            ]),
        );

        CliOutput::message("Success\n");
        return CliExitCode::OK;
    }

    private static function writeFile(string $file_path, string $content): void
    {
        $f = fopen($file_path, 'w');
        fwrite($f, $content);
        fclose($f);
    }
}