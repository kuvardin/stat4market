<?php

declare(strict_types=1);

namespace App\Api\v1\Models;

use App\Api\v1\ApiModelImmutable;
use App\Api\v1\Output\ApiField;
use App\Books\Book;

class BookApiModel extends ApiModelImmutable
{
    public function __construct(
        protected Book $book,
    )
    {

    }

    public static function getDescription(): ?string
    {
        return 'Книга';
    }

    public static function getFields(): array
    {
        return [
            'id' => ApiField::integer(false, 'ID'),
            'title' => ApiField::string(false, 'Наименование'),
            'isbn' => ApiField::string(false, 'ISBN'),
            'author' => ApiField::string(false, 'Автор'),
            'year_published' => ApiField::integer(true, 'Год издания'),
            'created_at' => ApiField::timestamp(false, 'Таймштамп создания записи'),
        ];
    }

    public function getPublicData(): array
    {
        return [
            'id' => $this->book->getId(),
            'title' => $this->book->getTitle(),
            'isbn' => $this->book->getIsbnValue(),
            'author' => $this->book->getAuthor(),
            'year_published' => $this->book->getYearPublished(),
            'created_at' => $this->book->getCreationDate(),
        ];
    }
}