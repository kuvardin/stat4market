<?php

declare(strict_types=1);

namespace App\Api\v1\Methods;

use App;
use App\Api\v1\ApiMethodImmutable;
use App\Api\v1\Exceptions\ApiException;
use App\Api\v1\Input\ApiInput;
use App\Api\v1\Input\ApiParameter;
use App\Api\v1\Output\ApiField;
use App\Books\Book;
use Kuvardin\TinyOrm\EntityAbstract;

class DeleteBooksWithId extends ApiMethodImmutable
{
    protected const string PARAM_ID = 'id';

    public static function getDescription(): ?string
    {
        return 'Удаление книги';
    }

    protected static function getParameters(): array
    {
        return [
            self::PARAM_ID => ApiParameter::integer(3001, 'ID книги'),
        ];
    }

    public static function getResultField(): ?ApiField
    {
        return null;
    }

    public static function handle(ApiInput $input)
    {
        $book_id = $input->requireInt(self::PARAM_ID);

        if (Book::findOneById($book_id) === null) {
            throw ApiException::withField(2005, self::PARAM_ID, $input);
        }

        App::pdo()
            ->getQueryBuilder()
            ->createDeleteQuery(Book::getEntityTableDefault())
            ->setWhere([
                EntityAbstract::COL_ID => $book_id,
            ])
            ->execute()
        ;

        return null;
    }
}