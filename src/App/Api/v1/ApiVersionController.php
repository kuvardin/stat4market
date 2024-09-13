<?php

declare(strict_types=1);

namespace App\Api\v1;

use App;
use App\Api\ApiVersionController as ApiVersionControllerAbstract;
use App\Api\v1\Exceptions\ApiException;
use App\Api\v1\Exceptions\IncorrectFieldValueException;
use App\Api\v1\Input\ApiInput;
use App\Api\v1\Interfaces\ApiModelIntIndexed;
use App\Api\v1\Interfaces\ApiModelStringIndexed;
use App\Api\v1\Models\ErrorApiModel;
use App\Api\v1\Output\ApiField;
use App\Api\v1\Output\ApiFieldType;
use App\Languages\Language;
use App\Languages\Locale;
use App\Languages\Phrase;
use App\Sessions\Session;
use App\Users\User;
use App\Utils\DateTime;
use App\Web\WebRequest;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;
use Throwable;

class ApiVersionController extends ApiVersionControllerAbstract
{
    protected const string PARAM_ID = 'id';

    public static function handle(WebRequest $request, array $route_parts): void
    {
        $input_data = [];
        $get = $request->get;

        if ($get !== []) {
            $input_data = $get;
        } else {
            $json_decoded_input = $request->getJsonDecodedInput();
            if (is_array($json_decoded_input)) {
                $input_data = $json_decoded_input;
            }
        }

        $current_timestamp = time();
        $throwables = [];

        $session = null;

        try {
            try {
                App::connectPdo();
                $language = new Language(Locale::RU);

                $postfix = '';
                $route_last_part = array_pop($route_parts);

                if (is_string($route_last_part)) {
                    if (!empty($route_last_part) && (string)(int)$route_last_part === $route_last_part) {
                        $postfix = 'WithId';
                        $input_data[self::PARAM_ID] = (int)$route_last_part;
                        $route_last_part = array_shift($route_parts);
                    }
                }

                if (is_string($route_last_part)) {
                    if (!str_starts_with($route_last_part, strtolower($request->method))) {
                        $route_last_part = strtolower($request->method) . ucfirst($route_last_part);
                    }

                    $route_last_part .= $postfix;
                    $route_parts[] = $route_last_part;
                }

                $method_class = self::getMethodClass($route_parts);
                if ($method_class === null || !class_exists($method_class)) {
                    throw ApiException::onlyCode(1002);
                }

                if ($request->token !== null) { // Get session from token
                    if (
                        $request->token->payload->isExpired($current_timestamp)
                        && $method_class !== Methods\Tokens\Refresh::class
                    ) {
                        throw ApiException::onlyCode(1003);
                    }
                }

                $user = null;
                if ($request->token?->payload->user_id !== null) {
                    $user = User::findOneById($request->token->payload->user_id);
                }

                if ($method_class::isOnlyForUsers() && $user === null) {
                    throw ApiException::onlyCode(2002);
                }

                $input = new ApiInput(
                    $method_class::getAllParameters($language->locale),
                    $input_data,
                    $language->locale,
                    $method_class::getSelectionOptions($language->locale),
                );

                if ($input->getException() !== null) {
                    throw $input->getException();
                }

                $method_result = $method_class::isMutable()
                    ? $method_class::handle($input, $session)
                    : $method_class::handle($input);

                $method_result_field = $method_class::getResultField();

                $public_data = null;
                if ($method_result === null) {
                    if ($method_result_field !== null && !$method_result_field->nullable) {
                        throw new RuntimeException("Method $method_class returns null");
                    }
                } else {
                    if ($method_result_field === null) {
                        throw new RuntimeException("Method $method_class must return null");
                    }

                    $public_data = self::processResult($session, $method_result_field, $method_result, null, 'result');
                }

                $result = [
                    'ok' => true,
                    'result' => $public_data,
                    'errors' => [],
                ];
            } catch (ApiException $exception) {
                http_response_code(400);
                throw $exception;
            } catch (Throwable $exception) {
                http_response_code(503);
                throw ApiException::onlyCode(1001, previous: $exception);
            }
        } catch (ApiException $api_exception) {
            $exceptions_public_data = [];

            do {
                if ($api_exception instanceof ApiException) {
                    $api_exception_model = new ErrorApiModel($api_exception);
                    $exceptions_public_data[] = self::processResult(
                        $session,
                        ApiField::object(ErrorApiModel::class, false),
                        $api_exception_model,
                        ErrorApiModel::class,
                        'error',
                    );
                } else {
                    $throwables[] = $api_exception;
                }
            } while ($api_exception = $api_exception->getPrevious());

            $result = [
                'ok' => false,
                'result' => null,
                'errors' => $exceptions_public_data,
            ];
        }

        $throwables_data = [];
        foreach ($throwables as $throwable) {
            $throwables_data[] = [
                'class' => get_class($throwable),
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'trace' => explode("\n", App::filterError($throwable->getTraceAsString())),
            ];
        }

        $result['service_info'] = [
            'generation_ms' => (microtime(true) - START_MICROTIME) * 1000,
            'throwables' => $throwables_data,
        ];

        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Expires: ' . date('r'));
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');

        echo json_encode($result, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws ApiException
     */
    private static function getSession(WebRequest $request, int $current_timestamp = null): Session
    {
        $current_timestamp ??= time();
        $reset_authorization = false;

        $session = null;

        if ($request->token !== null) {
            $session = Session::findOneById($request->token->payload->session_id);
            if (
                $session->getAuthorizationId() !== $request->token->payload->authorization_id
                || $session->getUserId() !== $request->token->payload->user_id
            ) {
                throw ApiException::onlyCode(1004);
            }
        }

        if ($session === null && $request->user_agent?->isWebBot()) {
            $session = Session::findOneByWebBotUserAgent($request->user_agent);
        }

        if ($session === null) {
            $session = Session::findOneByCookies($request->cookies, $request->ip_address, $request->user_agent);
            if ($session !== null) {
                $reset_authorization = true;
            }
        }

        if ($session === null) {
            $session = Session::create(
                $request->ip_address,
                $request->user_agent,
                last_request_date: $current_timestamp,
            );
        } else {
            $authorization = $session->getAuthorization();
            if (
                $authorization !== null
                && ($authorization->isDeleted() || !$authorization->isActive($current_timestamp))
            ) {
                $reset_authorization = true;
            }
        }

        if ($reset_authorization) {
            $authorization = $session->getAuthorization();
            if ($authorization !== null) {
                $authorization->delete(null, $current_timestamp);
                $authorization->saveChanges();
                $session->setAuthorization(null);
            }
        }

        $session->saveChanges();
        return $session;
    }

    protected static function processResult(
        ?Session $session,
        ApiField $field,
        mixed $value,
        ApiModel|string|null $model_class,
        string $field_name,
    ): mixed
    {
        $field_name_full = ($model_class === null ? '' : "$model_class -> ") . $field_name;

        if ($value === null) {
            if (!$field->nullable) {
                throw new RuntimeException("Field $field_name_full are null but not nullable");
            }

            return null;
        }

        switch ($field->type) {
            case ApiFieldType::String:
                if (!is_string($value)) {
                    throw new IncorrectFieldValueException($field_name, $model_class, $field, $value);
                }

                return $value;

            case ApiFieldType::Timestamp:
                if ($value instanceof DateTime) {
                    return $value->getTimestamp();
                }

                if (is_int($value)) {
                    return $value;
                }

                throw new IncorrectFieldValueException($field_name, $model_class, $field, $value);

            case ApiFieldType::Integer:
                if (!is_int($value)) {
                    throw new IncorrectFieldValueException($field_name, $model_class, $field, $value);
                }

                return $value;

            case ApiFieldType::Float:
                if (!is_float($value)) {
                    throw new IncorrectFieldValueException($field_name, $model_class, $field, $value);
                }

                return $value;

            case ApiFieldType::Boolean:
                if (!is_bool($value)) {
                    throw new IncorrectFieldValueException($field_name, $model_class, $field, $value);
                }

                return $value;

            case ApiFieldType::Object:
                if (!is_object($value) && !($value instanceof $field->model_class)) {
                    throw new IncorrectFieldValueException($field_name, $model_class, $field, $value);
                }

                $model_fields = $field->model_class::getFields();

                $result = [];
                $public_data = $value->getPublicData($session);
                foreach ($public_data as $public_data_field => $public_data_value) {
                    if (str_starts_with($public_data_field, '_')) {
                        $result[$public_data_field] = $public_data_value;
                        continue;
                    }

                    if (!array_key_exists($public_data_field, $model_fields)) {
                        throw new RuntimeException("Unknown {$field->model_class} field named $public_data_field");
                    }

                    $result[$public_data_field] = self::processResult(
                        $session,
                        $model_fields[$public_data_field],
                        $public_data_value,
                        $field->model_class,
                        $public_data_field,
                    );
                }

                foreach ($model_fields as $model_field_name => $model_field) {
                    if (str_starts_with($model_field_name, '_')) {
                        continue;
                    }

                    if (!array_key_exists($model_field_name, $public_data)) {
                        throw new RuntimeException("Field $model_field_name not found in {$field->model_class}");
                    }
                }

                return $result;

            case ApiFieldType::Phrase:
                if ($value instanceof Phrase) {
                    return $value->toArray();
                }

                throw new IncorrectFieldValueException($field_name, $model_class, $field, $value);

            case ApiFieldType::Array:
                if ($field->array_child_type === null) {
                    return $value;
                }

                $result = [];
                $child_field = $field->array_child_type === ApiFieldType::Object
                    ? ApiField::object($field->array_child_model_class, false)
                    : ApiField::scalar($field->array_child_type, false);

                foreach ($value as $array_item_key => $array_item_value) {

                    if (!is_int($array_item_key)) {
                        throw new RuntimeException('Array field must be disassociative');
                    }

                    $array_item_result_index = null;

                    if ($field->array_child_type === ApiFieldType::Object) {
                        if (
                            $field->array_child_model_index_type === ApiFieldType::Integer
                            || $field->array_child_model_index_type === ApiFieldType::String
                        ) {
                            $array_item_result_index = $array_item_value->getIndex();
                        }
                    }

                    $array_item_result = self::processResult(
                        $session,
                        $child_field,
                        $array_item_value,
                        $field->array_child_model_class,
                        '[array_item]',
                    );

                    if ($array_item_result_index === null) {
                        $result[] = $array_item_result;
                    } else {
                        $result[$array_item_result_index] = $array_item_result;
                    }
                }

                return $result;

            case ApiFieldType::Uuid:
                if ($value instanceof UuidInterface) {
                    return $value->toString();
                }

                if (!is_string($value) || !Uuid::isValid($value)) {
                    throw new RuntimeException("Incorrect UUID: $value");
                }

                return $value;

            default:
                throw new RuntimeException("Unknown type {$field->type->value}");
        }
    }

    public static function getMethodClass(array $route_parts): string|ApiMethod|null
    {
        $result = 'App\\Api\\v1\\Methods';
        foreach ($route_parts as $route_part) {
            if ($route_part === '') {
                continue;
            }

            $route_part_ucfirst = ucfirst($route_part);
            if ($route_part_ucfirst === $route_part) {
                return null;
            }

            $result .= '\\' . $route_part_ucfirst;
        }

        return $result;
    }
}