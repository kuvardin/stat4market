<?php

declare(strict_types=1);

namespace App\Api\v1\Models;

use App\Api\v1\ApiModelImmutable;
use App\Api\v1\ApiSelectionData;
use App\Api\v1\Output\ApiField;
use App\Books\Book;

class BooksListApiModel extends ApiModelImmutable
{
    /**
     * @param Book[] $books
     */
    public function __construct(
        protected array $books,
        protected ApiSelectionData $selection_data,
    )
    {

    }

    public static function getDescription(): ?string
    {
        return 'Выборка книг';
    }

    public static function getFields(): array
    {
        return [
            'books' => ApiField::arrayOfObjects(BookApiModel::class, description: 'Книги'),
            'selection_data' => ApiField::object(SelectionDataApiModel::class, false, 'Данные о выборке'),
        ];
    }

    public function getPublicData(): array
    {
        return [
            'books' => array_map(
                static fn(Book $book) => new BookApiModel($book),
                $this->books,
            ),
            'selection_data' => new SelectionDataApiModel($this->selection_data),
        ];
    }
}