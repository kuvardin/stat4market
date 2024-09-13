<?php

declare(strict_types=1);

namespace App\Api\v1\Methods;

use App;
use App\Api\v1\ApiMethodImmutable;
use App\Api\v1\ApiSelectionOptions;
use App\Api\v1\Exceptions\ApiException;
use App\Api\v1\Input\ApiInput;
use App\Api\v1\Input\ApiParameter;
use App\Api\v1\Models\BooksListApiModel;
use App\Api\v1\Output\ApiField;
use App\Books\Book;
use App\Languages\Locale;
use Kuvardin\TinyOrm\Conditions\ConditionsList;
use Kuvardin\TinyOrm\EntityAbstract;
use Kuvardin\TinyOrm\Enums\SortDirection;

class GetBooks extends ApiMethodImmutable
{
    protected const string PARAM_QUERY  = 'query';
    protected const string PARAM_YEAR_PUBLISHED  = 'year_published';

    public static function getDescription(): ?string
    {
        return 'Получение выборки книг';
    }

    public static function getResultField(): ?ApiField
    {
        return ApiField::object(BooksListApiModel::class, false);
    }

    protected static function getParameters(): array
    {
        return [
            self::PARAM_QUERY => ApiParameter::string(null, 'Поисковый запрос'),
            self::PARAM_YEAR_PUBLISHED => ApiParameter::integer(null, 'Дата публикациия'),
        ];
    }

    public static function getSelectionOptions(Locale $language_code,): ?ApiSelectionOptions
    {
        return new ApiSelectionOptions(EntityAbstract::COL_ID, SortDirection::Desc, [
            Book::COL_TITLE,
            Book::COL_YEAR_PUBLISHED,
            Book::COL_AUTHOR,
            Book::COL_ISBN,
        ]);
    }

    public static function handle(ApiInput $input): BooksListApiModel
    {
        $expr = App::pdo()->expr();
        $conditions = new ConditionsList();
        $table = Book::getEntityTableDefault();
        $query = $input->getString(self::PARAM_QUERY);
        $year_published = $input->getInt(self::PARAM_YEAR_PUBLISHED);

        if ($query !== null) {
            $conditions->appendExpression(
                $expr->or(
                    $expr->ilike(
                        $table->getColumn(Book::COL_TITLE),
                        '%' . $query . '%',
                    ),
                    $expr->ilike(
                        $table->getColumn(Book::COL_AUTHOR),
                        '%' . $query . '%',
                    )
                ),
            );
        }

        if ($year_published !== null) {
            $conditions->appendExpression(
                $expr->equal(
                    $table->getColumn(Book::COL_YEAR_PUBLISHED),
                    $year_published,
                ),
            );
        }

        $total_amount = Book::countByConditions($conditions, table: $table);

        if (!$total_amount) {
            throw ApiException::onlyCode(2006);
        }

        $selection_data = $input->requireSelectionData($total_amount);
        $books = Book::findByConditions(
            conditions: $conditions,
            sorting_settings: $selection_data->getSortingSettings(),
            limit: $selection_data->getLimit(),
            offset: $selection_data->getOffset(),
            table: $table,
        );

        return new BooksListApiModel(
            books: iterator_to_array($books),
            selection_data: $selection_data,
        );
    }
}