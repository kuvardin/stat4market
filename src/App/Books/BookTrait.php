<?php

declare(strict_types=1);

namespace App\Books;

trait BookTrait
{
    protected ?int $book_id;

    public function getBookId(): ?int
    {
        return $this->book_id;
    }

    public function getBook(): ?Book
    {
        return $this->book_id === null
            ? null
            : Book::requireOneById($this->book_id);
    }
}
