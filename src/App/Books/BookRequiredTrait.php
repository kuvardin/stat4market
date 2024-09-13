<?php

declare(strict_types=1);

namespace App\Books;

trait BookRequiredTrait
{
    protected int $book_id;

    public function getBookId(): int
    {
        return $this->book_id;
    }

    public function getBook(): Book
    {
        return Book::requireOneById($this->book_id);
    }
}
