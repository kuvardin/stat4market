<?php

declare(strict_types=1);

use Kuvardin\TinyOrm\Connection;
use Kuvardin\TinyOrm\Loggers\FileLogger;

class App
{
    protected static array $settings;
    protected static ?Connection $connection = null;

    protected static string $domain;

    public static function init(array $settings): void
    {
        self::$settings = $settings;
    }

    public static function connectPdo(): void
    {
        if (self::$connection === null) {
            self::$connection = Connection::create(
                adapter: self::$settings['db.adapter'],
                host: self::$settings['db.host'],
                port: self::$settings['db.port'],
                base: self::settings('db.base'),
                username: self::$settings['db.user'],
                password: self::$settings['db.pass'],
                options: [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::PGSQL_ATTR_DISABLE_PREPARES => true,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
                is_default: true,
            );

            self::$connection->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
            self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            $db_logs_dir = App::settings('db.logs_dir');
            if (!empty($db_logs_dir)) {
                $date = (new DateTime())->format('Ymd_His_u');
                self::requireDir($db_logs_dir);
                self::$connection->logger = new FileLogger("$db_logs_dir/$date.log");
            }
        }
    }

    public static function pdo(): Connection
    {
        return self::$connection;
    }

    public static function settings(string $key): mixed
    {
        return self::$settings[$key];
    }

    public static function shortenString(string $string, int $max_length): string
    {
        return mb_strlen($string) > $max_length
            ? rtrim(mb_substr($string, 0, $max_length - 2), " \n\r\t\0\x0B\"-~`!@#$%^&*()_+=,./?<>|\\[]{}") . '..'
            : $string;
    }

    /**
     * @param resource|null $context
     */
    public static function requireDir(string $pathname, bool $recursive = true, int $mode = 0777, $context = null): void
    {
        if (!file_exists($pathname) && !mkdir($pathname, $mode, $recursive, $context) && !is_dir($pathname)) {
            throw new RuntimeException("Directory $pathname was not created");
        }
    }

    public static function getRandomString(int $length, string $alphabet = null): string
    {
        if ($length < 1) {
            throw new Error('String length must pe positive number');
        }

        $alphabet ??= 'abcdefghijklmnopqrstuvwxyz0123456789';
        $alphabet_length = mb_strlen($alphabet);
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            try {
                $result .= mb_substr($alphabet, random_int(0, $alphabet_length - 1), 1);
            } catch (Exception $e) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }

        return $result;
    }

    public static function getGenerationTime(float $start_time = null): float
    {
        return (microtime(true) - ($start_time ?? START_MICROTIME)) * 1000;
    }

    public static function roundNum(int|float $number, int $precision = null): string
    {
        if ($number > 1000000) {
            return round($number / 100000, 1) . 'M';
        }

        if ($number > 10000) {
            return round($number / 1000, 1) . 'k';
        }

        if ($number > 1000) {
            return round($number / 1000, 2) . 'k';
        }

        if (is_float($number)) {
            return (string)($precision === null ? $number : round($number, $precision));
        }

        return (string)$number;
    }

    public static function htmlFilter(?string $text): ?string
    {
        return $text === null ? null : htmlspecialchars($text);
    }

    public static function setCookie(string $name, ?string $value): void
    {
        setcookie(
            $name,
            $value ?? '',
            App::settings('cookies.expires'),
            App::settings('cookies.path'),
            App::settings('cookies.domain'),
        );
    }

    public static function checkStringLength(
        string $string,
        int $min = null,
        int $max = null,
        bool $use_mbstring = true,
    ): bool
    {
        $length = $use_mbstring ? mb_strlen($string) : strlen($string);
        if ($min !== null && $length < $min) {
            return false;
        }

        if ($max !== null && $length > $max) {
            return false;
        }

        return true;
    }

    public static function filterError(string $error): string
    {
        return str_replace('/var/www/backend', '', $error);
    }

    public static function isList(array &$array, string $class = null): bool
    {
        $i = 0;

        foreach ($array as $key => $value) {
            if ($key !== $i++) {
                return false;
            }

            if ($class !== null && !(is_object($value) && get_class($value) === $class)) {
                return false;
            }
        }

        return true;
    }
}