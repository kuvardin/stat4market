<?php

declare(strict_types=1);

namespace App\Api\v1\Methods;

use App\Api\v1\ApiMethodImmutable;
use App\Api\v1\Exceptions\ApiException;
use App\Api\v1\Input\ApiInput;
use App\Api\v1\Input\ApiParameter;
use App\Api\v1\Models\BookApiModel;
use App\Api\v1\Output\ApiField;
use App\Books\Book;
use App\Utils\Isbn;

class PutBooksWithId extends ApiMethodImmutable
{
    protected const string PARAM_ID = 'id';
    protected const string PARAM_TITLE = 'title';
    protected const string PARAM_AUTHOR = 'author';
    protected const string PARAM_YEAR_PUBLISHED = 'year_published';
    protected const string PARAM_CLEAR_YEAR_PUBLISHED = 'clear_year_published';
    protected const string PARAM_ISBN = 'isbn';

    public static function getDescription(): ?string
    {
        return 'Редактирование книги';
    }

    protected static function getParameters(): array
    {
        return [
            self::PARAM_ID => ApiParameter::integer(3001, 'ID книги'),
            self::PARAM_TITLE => ApiParameter::string(null, 'Наименование'),
            self::PARAM_AUTHOR => ApiParameter::string(null, 'Автор'),
            self::PARAM_YEAR_PUBLISHED => ApiParameter::integer(null, 'Год публикации'),
            self::PARAM_CLEAR_YEAR_PUBLISHED => ApiParameter::boolean(null, 'Очистить год публикации'),
            self::PARAM_ISBN => ApiParameter::string(null, 'ISBN'),
        ];
    }

    public static function getResultField(): ?ApiField
    {
        return ApiField::object(BookApiModel::class, false);
    }

    public static function handle(ApiInput $input): BookApiModel
    {
        $book_id = $input->requireInt(self::PARAM_ID);
        $book = Book::findOneById($book_id) ?? throw ApiException::withField(2005, self::PARAM_ID, $input);
        $title = $input->getString(self::PARAM_TITLE);
        $author = $input->getString(self::PARAM_AUTHOR);
        $year_published = $input->getInt(self::PARAM_YEAR_PUBLISHED);
        $clear_year_published = $input->getBool(self::PARAM_CLEAR_YEAR_PUBLISHED);
        $isbn_value = $input->getString(self::PARAM_ISBN);

        if ($isbn_value !== null) {
            $isbn = Isbn::tryFromString($isbn_value) ?? throw ApiException::withField(3005, self::PARAM_ISBN, $input);

            if (Book::findOneByIsbn($isbn) !== null) {
                throw ApiException::withField(2004, self::PARAM_ISBN, $input);
            }

            $book->setIsbn($isbn);
        }

        if ($title !== null) {
            $book->setTitle($title);
        }

        if ($author !== null) {
            $book->setAuthor($author);
        }

        if ($clear_year_published) {
            $book->setYearPublished(null);
        } elseif ($year_published !== null) {
            $book->setYearPublished($year_published);
        }

        $book->saveChanges();

        return new BookApiModel($book);
    }
}