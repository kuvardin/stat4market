<?php

declare(strict_types=1);

namespace App\Utils;

use App;
use App\StoreParsers\Exceptions\ParserException;
use RuntimeException;
use Throwable;

class Logger
{
    /**
     * @param Throwable $exception
     */
    public static function writeException(Throwable $exception): void
    {
        $exception_class = get_class($exception);
        $text = (new DateTime())->format('YmdHisu') . ": $exception_class #{$exception->getCode()}\n" .
            "\tFile: {$exception->getFile()}:{$exception->getLine()}\n" .
            "\tMessage: {$exception->getMessage()}\n" .
            "\tTrace: {$exception->getTraceAsString()}\n\n";
        self::writeToFile(self::getErrorsLogFilePath(), $text);
    }

    public static function writeToFile(string $file_path, string $string, bool $append = true): void
    {
        $f = fopen($file_path, $append ? 'a' : 'w');
        if ($f === false) {
            throw new RuntimeException("File $file_path opening failed");
        }

        if (fwrite($f, $string) === false) {
            throw new RuntimeException("Writing fo file $file_path failed");
        }

        if (!fclose($f)) {
            throw new RuntimeException("File $file_path closing failed");
        }
    }

    public static function getErrorsLogFilePath(): string
    {
        $dir = LOGS_DIR . '/errors';
        App::requireDir($dir);
        return $dir . '/' . (new DateTime())->format('Ymd') . '.log';
    }

    public static function writeError(string $error): void
    {
        self::writeToFile(self::getErrorsLogFilePath(), $error);
    }

    public static function writeParserException(ParserException $parser_exception): void
    {
        $parser = $parser_exception->getParser();
        $dir = LOGS_DIR . '/store_parsers/' . $parser::getCode()->value;
        App::requireDir($dir);
        $file_path = $dir . '/' . (new DateTime())->format('Ymd_His_u') . '.log';
        self::writeToFile($file_path, sprintf(
            "Error #%d: %s\n\n%s\n\n%s",
            $parser_exception->getCode(),
            $parser_exception->getMessage(),
            $parser_exception->getRequestInfo(),
            $parser_exception->getResponseInfo(),
        ));
    }
}