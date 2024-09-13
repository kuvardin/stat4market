<?php

declare(strict_types=1);

use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Migration\AbstractMigration;

final class Users extends AbstractMigration
{
    private const string TABLE_USERS = 'users';

    public function up(): void
    {
        $users_table =  $this->table(self::TABLE_USERS, [
            'comment' => 'Пользователи',
        ]);

        $users_table
            ->addColumn('phone_number', 'string', [
                'limit' => 32,
                'null' => false,
                'encoding' => 'ascii',
            ])
            ->addColumn('username', 'string', [
                'limit' => 32,
                'null' => true,
                'encoding' => 'ascii',
            ])
            ->addColumn('password_hash', 'string', [
                'limit' => 255,
                'null' => true,
                'encoding' => 'ascii',
            ])
            ->addColumn('first_name', 'string', [
                'limit' => 32,
                'null' => true,
                'encoding' => 'utf8',
            ])
            ->addColumn('last_name', 'string', [
                'limit' => 32,
                'null' => true,
                'encoding' => 'utf8',
            ])
            ->addColumn('middle_name', 'string', [
                'limit' => 32,
                'null' => true,
                'encoding' => 'utf8',
            ])
            ->addColumn('last_request_date', 'integer', [
                'null' => true,
            ])
            ->addColumn('creation_date', 'integer', [
                'null' => false,
            ])
            ->save()
        ;

        $users_table
            ->addIndex('phone_number', [
                'unique' => true,
            ])
            ->addIndex('username', [
                'unique' => true,
            ])
            ->save();
    }

    public function down(): void
    {
        $this->table(self::TABLE_USERS)->drop()->save();
    }
}
