<?php

declare(strict_types=1);

namespace App\Api\v1;

use App\Api\v1\Exceptions\ApiException;
use App\Languages\Locale;
use Throwable;
use RuntimeException;

class ApiReflection
{
    protected const string API_MODELS_DIR = '/src/App/Api/v1/Models';
    protected const string API_MODELS_NAMESPACE = 'App\\Api\\v1\\Models';

    protected const string API_METHODS_DIR = '/src/App/Api/v1/Methods';
    protected const string API_METHODS_NAMESPACE = 'App\\Api\\v1\\Methods';

    private function __construct()
    {
    }

    /**
     * @param string[]|ApiMethod[] $result
     * @param string[] $errors
     * @param string[] $parents
     * @return ApiMethod[]|string[]
     */
    public static function getApiMethods(array &$result, array &$errors, array $parents = []): array
    {
        $directory = ROOT_DIR . self::API_METHODS_DIR . '/' . implode('/', $parents);
        $files = scandir($directory);

        foreach ($files as $file_path) {
            if ($file_path === '.' || $file_path === '..') {
                continue;
            }

            if (is_dir($directory . '/' . $file_path)) {
                self::getApiMethods($result, $errors, array_merge($parents, [$file_path]));
                continue;
            }

            if (!preg_match('|^(.+?)\.php$|', $file_path, $match)) {
                $errors[] = "Incorrect method class file name: $file_path";
                continue;
            }

            $method_name = $match[1];

            $http_method = self::getHttpMethod($method_name);

            $method_full_path = $directory . '/' . $file_path;

            /** @var ApiMethod|string $method_class */
            $method_class = $parents === []
                ? self::API_METHODS_NAMESPACE . '\\' . $method_name
                : self::API_METHODS_NAMESPACE . '\\' . implode('\\', $parents) . '\\' . $method_name;

            try {
                $success = require_once $method_full_path;
                $method_class::getResultField();

                $method_public_name = '';
                foreach ($parents as $parent) {
                    $method_public_name .= lcfirst($parent) . '/';
                }

                $method_public_name .= lcfirst($method_name);


                $result[$method_public_name] = [
                    'http_method' => $http_method,
                    'class' => $method_class,
                    'errors' => self::getMethodErrors($method_full_path, $method_class),
                ];
            } catch (Throwable $exception) {
                $errors[] = "Method class $file_path has error: {$exception->getMessage()}";
                continue;
            }
        }

        return $result;
    }

    private static function getHttpMethod(string $api_method_name): string
    {
        $api_method_name = lcfirst($api_method_name);

        return match (true) {
            str_starts_with($api_method_name, 'get') => 'GET',
            str_starts_with($api_method_name, 'post') => 'POST',
            str_starts_with($api_method_name, 'put') => 'PUT',
            str_starts_with($api_method_name, 'delete') => 'DELETE',
            str_starts_with($api_method_name, 'head') => 'HEAD',
            str_starts_with($api_method_name, 'options') => 'OPTIONS',
            default => throw new RuntimeException("Wrong API method name: $api_method_name"),
        };
    }

    /**
     * @return int[]
     */
    public static function getMethodErrors(string $method_full_path, string|ApiMethod $method_class): array
    {
        $script = file_get_contents($method_full_path);
        if ($script === false) {
            throw new RuntimeException("Error reading file: $method_full_path");
        }

        $result = [
            ApiException::INTERNAL_SERVER_ERROR,
        ];

        if (preg_match_all('|ApiException(::[a-zA-Z]+)?\((\d+)|', $script, $exception_matches, PREG_SET_ORDER)) {
            foreach ($exception_matches as $exception_match) {
                $exception_code = (int)$exception_match[2];
                if (!in_array($exception_code, $result, true)) {
                    $result[] = $exception_code;
                }
            }
        }

        $parameters = $method_class::getAllParameters(Locale::RU);
        foreach ($parameters as $parameter) {
            if (
                $parameter->required_and_empty_error !== null
                && !in_array($parameter->required_and_empty_error, $result, true)
            ) {
                $result[] = $parameter->required_and_empty_error;
            }
        }

        sort($result);
        return $result;
    }

    public static function getApiModels(array &$errors, Locale $language_code): array
    {
        /**
         * @var string[]|ApiModel[] $result
         */
        $result = [];

        $files = scandir(ROOT_DIR . self::API_MODELS_DIR);
        foreach ($files as $file_path) {
            if (is_dir(ROOT_DIR . self::API_MODELS_DIR . '/' . $file_path)) {
                continue;
            }

            if (!preg_match('|^(.+?)ApiModel\.php$|', $file_path, $match)) {
                $errors[] = "Incorrect model class name: $file_path";
                continue;
            }

            $model_name = $match[1];
            $model_full_path = ROOT_DIR . self::API_MODELS_DIR . '/' . $file_path;
            try {
                $success = require_once $model_full_path;

                /** @var ApiModel|string|null $model_class */
                $model_class = self::API_MODELS_NAMESPACE . '\\' . $model_name . 'ApiModel';

                $model_class::getFields();

                $result[$model_name] = $model_class;
            } catch (Throwable $exception) {
                $errors[] = "Model class $file_path has error: {$exception->getMessage()}";
                continue;
            }
        }

        return $result;
    }
}
