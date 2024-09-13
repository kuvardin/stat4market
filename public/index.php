<?php

declare(strict_types=1);

error_reporting(E_ALL);

$execution_time_limit = empty($argv) ? 60 : 0;
set_time_limit($execution_time_limit);
ini_set('max_execution_time', $execution_time_limit);

define('ROOT_DIR', dirname(__DIR__));
define('START_MICROTIME', microtime(true));

const CACHE_DIR = ROOT_DIR . '/cache';
const CLASSES_DIR = ROOT_DIR . '/src';
const COOKIES_DIR = ROOT_DIR . '/cookies';
const PUBLIC_DIR = ROOT_DIR . '/public';
const LOGS_DIR = ROOT_DIR . '/logs';
const PHRASES_DIR = ROOT_DIR . '/phrases';
const TEMPLATES_DIR = ROOT_DIR . '/templates';
const INCLFILES_DIR = ROOT_DIR . '/inclfiles';
const TEMP_DIR = '/tmp';
const FILES_DIR = PUBLIC_DIR . '/files';
const CERTS_DIR = ROOT_DIR . '/certs';
const IMAGES_DIR = PUBLIC_DIR . '/images';

$settings = require ROOT_DIR . '/settings.php';
try {
    $settings = array_merge($settings, require ROOT_DIR . '/settings.local.php');
} catch (Throwable) {

}

date_default_timezone_set($settings['timezone.default']);

try {
    set_error_handler(
        static function (int $code, string $message, string $file, int $line) {
            $error_text = sprintf("Handled by error handler. Error #%d: %s on %s:%d\n", $code, $message, $file, $line);
            throw new Error($error_text, $code);
        },
    );

    register_shutdown_function(
        static function () {
            $error = error_get_last();
            if ($error !== null) {
                $error_text = sprintf(
                    "Handled by shutdown function. Fatal error #%s: %s on %s:%d",
                    $error['type'],
                    $error['message'],
                    $error['file'],
                    $error['line'],
                );

                throw new Error($error_text);
            }
        },
    );

    require ROOT_DIR . '/vendor/autoload.php';
    require CLASSES_DIR . '/App.php';

    App\Utils\DateTime::cacheTimestamp(time());

    if (!empty($argv)) {
        ini_set('memory_limit', '512M');

        array_shift($argv);

        App::init($settings);
        $result_status = App\Cli\CliController::handle($argv);
        exit($result_status);
    }

    App::init($settings);

    $route = trim(preg_replace('|\?.*$|', '', $_SERVER['REQUEST_URI']), '/');
    $route_parts = explode('/', $route);

    $user_agent = null;
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $user_agent_value = trim($_SERVER['HTTP_USER_AGENT']);
        if (!empty($user_agent_value)) {
            if (strlen($user_agent_value) > 512) {
                $user_agent_value = substr($user_agent_value, 0, 512);
            }

            $user_agent = new App\Sessions\UserAgent($user_agent_value);
        }
    }

    $input_content = file_get_contents('php://input');
    $input_string = $input_content === false || $input_content === '' ? null : $input_content;

    $referrer = isset($_SERVER['HTTP_REFERER'])
        ? Kuvardin\DataFilter\DataFilter::getString($_SERVER['HTTP_REFERER'], empty_to_null: true)
        : null;

    $ip_address = App\Sessions\IpAddress::makeFromServer($_SERVER);
    if ($ip_address === null) {
        throw new RuntimeException('IP not found');
    }

    $token_value = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    $token = empty($token_value) ? null : App\Jwt\Token::makeFromHttpHeader($token_value);

    $web_request = new App\Web\WebRequest(
        method: $_SERVER['REQUEST_METHOD'],
        get: $_GET,
        post: $_POST,
        ip_address: $ip_address,
        user_agent: $user_agent,
        cookies: $_COOKIE,
        route: $route,
        input_string: $input_string,
        referrer: $referrer,
        token: $token,
    );

    switch ($route_parts[0] ?? null) {
        case 'api':
            if (isset($route_parts[1]) && $route_parts[1] === 'v1_doc') {
                App\Api\v1\Documentation::handle($web_request);
                break;
            }

            array_shift($route_parts);
            App\Api\ApiController::handle($web_request);
            break;

        default:
            App\Site\SiteController::handleRequest($web_request);
    }
} catch (Throwable $exception) {
    http_response_code(500);
    $error_text = "Handled by index.php<br>\n<pre>$exception</pre>";
    echo $error_text;
}