<?php

declare(strict_types=1);

namespace App\Api\v1;

use App\Api\v1\Input\ApiParameter;
use App\Api\v1\Output\ApiField;
use App\Languages\Locale;

abstract class ApiMethod
{
    private function __construct()
    {
    }

    /**
     * @return ApiParameter[]
     */
    protected static function getParameters(): array
    {
        return [];
    }

    final public static function getAllParameters(
        Locale $language_code,
        bool $required = null,
    ): array
    {
        $parameters = static::getParameters();

        if (static::getSelectionOptions($language_code) !== null) {
            $parameters = array_merge(ApiSelectionOptions::getApiParameters(), $parameters);
        }

        if ($required !== null) {
            $result = [];

            foreach ($parameters as $parameter_name => $parameter) {
                if ($parameter->isRequired() === $required) {
                    $result[$parameter_name] = $parameter;
                }
            }

            return $result;
        }

        return $parameters;
    }

    /**
     * @return ApiSelectionOptions|null Только если в методе используется пагинация
     */
    public static function getSelectionOptions(
        Locale $language_code,
    ): ?ApiSelectionOptions
    {
        return null;
    }

    public static function getDescription(): ?string
    {
        return null;
    }

    abstract public static function getResultField(): ?ApiField;

    abstract public static function isMutable(): bool;

    public static function isOnlyForUsers(): bool
    {
        return false;
    }
}
