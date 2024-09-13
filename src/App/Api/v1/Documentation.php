<?php

declare(strict_types=1);

namespace App\Api\v1;

use App\Languages\Locale;
use App\TemplatesEngine\TemplatesEngine;
use App\Web\WebRequest;

class Documentation
{
    public static function handle(WebRequest $web_request): void
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

        /** @var array<string, string> $methods_http_methods */
        $methods_http_methods = [];

        foreach ($methods_data as $method_name => $method_data) {
            $methods[$method_name] = $method_data['class'];
            $methods_error_codes[$method_name] = $method_data['errors'];
            $methods_http_methods[$method_name] = $method_data['http_method'];
        }

        $about_documentation = 'Generated documentation';

        $content = TemplatesEngine::render('api/v1_doc', [
            'title' => 'API v1',
            'about_documentation' => $about_documentation,
            'language_code' => $language_code,
            'models' => $models,
            'models_errors' => $models_errors,
            'methods' => $methods,
            'methods_errors' => $methods_errors,
            'methods_error_codes' => $methods_error_codes,
            'methods_http_methods' => $methods_http_methods,
        ]);

        exit($content);
    }
}