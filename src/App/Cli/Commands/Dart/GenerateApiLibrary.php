<?php

declare(strict_types=1);

namespace App\Cli\Commands\Dart;

use App\Api\v1\ApiMethod;
use App\Api\v1\ApiReflection;
use App\Cli\CliCommand;
use App\Cli\CliExitCode;
use App\Cli\Input\CliInput;
use App\Languages\Locale;
use App\TemplatesEngine\TemplatesEngine;

class GenerateApiLibrary extends CliCommand
{
    public static function execute(CliInput $input): int
    {
        $language_code = Locale::RU;

        $models_errors = [];
        $models = ApiReflection::getApiModels($models_errors, $language_code);

        /** @var ApiMethod[]|string[] $methods */
        $methods = [];
        $methods_errors = [];
        $methods_data = [];

        ApiReflection::getApiMethods($methods_data, $methods_errors);

        /** @var int[][] $methods_error_codes */
        $methods_error_codes = [];
        foreach ($methods_data as $method_name => $method_data) {
            $methods[$method_name] = $method_data['class'];
            $methods_error_codes[$method_name] = $method_data['errors'];
        }

        $content = TemplatesEngine::render('dart/api/api_library', [
            'title' => 'API v1',
            'language_code' => $language_code,
            'models' => $models,
            'models_errors' => $models_errors,
            'methods' => $methods,
            'methods_errors' => $methods_errors,
            'methods_error_codes' => $methods_error_codes,
        ]);

        $result_file_path = ROOT_DIR . '/dart/api.dart';
        $f = fopen($result_file_path, 'w');
        fwrite($f, $content);
        fclose($f);

        return CliExitCode::OK;
    }
}