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

class GetBooksWithId extends ApiMethodImmutable
{
    protected const string PARAM_ID = 'id';

    public static function getDescription(): ?string
    {
        return 'Получение книги';
    }

    protected static function getParameters(): array
    {
        return [
            self::PARAM_ID => ApiParameter::integer(3001, 'ID книги'),
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
        return new BookApiModel($book);
    }
}