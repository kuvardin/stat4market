<?php

use App\Api\v1\Output\ApiField;
use App\Api\v1\Output\ApiFieldType;
use App\Utils\CaseSwitcher;

/**
 * @var string $name
 * @var ApiField $field
 */

$dart_name = CaseSwitcher::snakeToCamel($name);

echo "$dart_name = ";
if ($field->nullable && (!$field->type->isScalar() || $field->type === ApiFieldType::Phrase)) {
    echo "data['$name'] == null\n\t\t? null\n\t\t: ";
}

switch ($field->type) {
    case ApiFieldType::Phrase;
        echo  "Phrase.fromMap(Map<String, String?>.from(data['$name']))";
        break;

    case ApiFieldType::Object:
        echo $field->model_class::getName(), ".fromMap(data['$name'])";
        break;

    case ApiFieldType::Array:
        if ($field->array_child_type->isScalar()) {
            echo "List<{$field->array_child_type->getDartType()}>.from(data['$name'] as List)";
        } elseif ($field->isMap()) {
            printf(
                "(data['%s'] as Map<String, dynamic>).map<%s, %s>((key, value) => MapEntry<%s, %s>(%s, %s))",
                $name,
                $field->array_child_model_index_type->getDartType(),
                $field->array_child_model_class === null
                    ? $field->array_child_type->getDartType()
                    : $field->array_child_model_class::getName(),
                $field->array_child_model_index_type->getDartType(),
                $field->array_child_model_class === null
                    ? $field->array_child_type->getDartType()
                    : $field->array_child_model_class::getName(),
                $field->array_child_model_index_type === ApiFieldType::Integer
                    ? 'int.parse(key)'
                    : 'key',
                $field->array_child_type === ApiFieldType::Object
                    ? sprintf('%s.fromMap(value)', $field->array_child_model_class === null ? $field->array_child_type->getDartType() : $field->array_child_model_class::getName())
                    : 'value',
            );
        } else {
            echo "data['$name'].map<{$field->array_child_model_class::getName()}>((itemData) => {$field->array_child_model_class::getName()}.fromMap(itemData)).toList()";
        }
        break;

    default:
        echo "data['$name']";
}