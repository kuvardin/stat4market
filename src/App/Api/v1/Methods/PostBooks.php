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
use Kuvardin\TinyOrm\Exception\AlreadyExists;

class PostBooks extends ApiMethodImmutable
{
    protected const string PARAM_TITLE = 'title';
    protected const string PARAM_AUTHOR = 'author';
    protected const string PARAM_YEAR_PUBLISHED = 'year_published';
    protected const string PARAM_ISBN = 'isbn';

    public static function getDescription(): ?string
    {
        return 'Создание книги';
    }

    protected static function getParameters(): array
    {
        return [
            self::PARAM_TITLE => ApiParameter::string(3002, 'Наименование'),
            self::PARAM_AUTHOR => ApiParameter::string(3003, 'Автор'),
            self::PARAM_YEAR_PUBLISHED => ApiParameter::integer(null, 'Дата год публикации'),
            self::PARAM_ISBN => ApiParameter::string(3004, 'ISBN'),
        ];
    }

    public static function getResultField(): ?ApiField
    {
        return ApiField::object(BookApiModel::class, false);
    }

    public static function handle(ApiInput $input): BookApiModel
    {
        $title = $input->requireString(self::PARAM_TITLE);
        $author = $input->requireString(self::PARAM_AUTHOR);
        $year_published = $input->getInt(self::PARAM_YEAR_PUBLISHED);
        $isbn_value = $input->requireString(self::PARAM_ISBN);

        $isbn = Isbn::tryFromString($isbn_value) ?? throw ApiException::withField(3005, self::PARAM_ISBN, $input);

        try {
            $book = Book::create(
                isbn: $isbn,
                title: $title,
                author: $author,
                year_published: $year_published,
            );

            return new BookApiModel($book);
        } catch (AlreadyExists) {
            throw ApiException::onlyCode(2004);
        }
    }
}