<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Books extends AbstractMigration
{
    private const string TABLE_BOOKS = 'books';

    public function up(): void
    {
        $books_table =  $this->table(self::TABLE_BOOKS, [
            'comment' => 'Книги',
        ]);

        $books_table
            ->addColumn('isbn', 'string', [
                'null' => false,
                'limit' => 63,
            ])
            ->addColumn('title', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('author', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('year_published', 'integer', [
                'signed' => false,
                'null' => true,
            ])
            ->addColumn('creation_date', 'integer', [
                'null' => false,
            ])
            ->create()
        ;

        $books_table
            ->addIndex('isbn', [
                'unique' => true,
            ])
            ->save()
        ;
    }

    public function down(): void
    {
        $this->table(self::TABLE_BOOKS)->drop()->save();
    }
}
