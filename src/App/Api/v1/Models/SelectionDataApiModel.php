<?php

declare(strict_types=1);

namespace App\Api\v1\Models;

use App\Api\v1\ApiModelImmutable;
use App\Api\v1\ApiSelectionData;
use App\Api\v1\Output\ApiField;
use App\Api\v1\Output\ApiFieldType;

class SelectionDataApiModel extends ApiModelImmutable
{
    protected ApiSelectionData $selection_data;

    public function __construct(ApiSelectionData $selection_data)
    {
        $this->selection_data = $selection_data;
    }

    public static function getDescription(): ?string
    {
        return 'Информация о выборке';
    }

    public static function getFields(): array
    {
        return [
            'limit' => ApiField::integer(false, 'Лимит'),
            'total_amount' => ApiField::integer(false, 'Общее количество элементов'),
            'page' => ApiField::integer(false, 'Страница'),
            'pages_total' => ApiField::integer(false, 'Общее количество страниц'),
            'sort_by' => ApiField::string(false, 'Поле, по которому производится сортировка'),
            'sort_direction' => ApiField::string(false, 'Направление сортировки'),
            'sort_variants' => ApiField::arrayOfScalar(ApiFieldType::String, description: 'Варианты полей сортировки'),
        ];
    }

    public function getPublicData(): array
    {
        return [
            'limit' => $this->selection_data->getLimit(),
            'total_amount' => $this->selection_data->total_amount,
            'page' => $this->selection_data->getPage(),
            'pages_total' => $this->selection_data->getPagesNumber(),
            'sort_by' => $this->selection_data->getSortBy(),
            'sort_direction' => $this->selection_data->sort_direction->value,
            'sort_variants' => array_keys($this->selection_data->sort_by_variants),
        ];
    }
}
