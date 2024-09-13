<?php

declare(strict_types=1);

namespace App\Cli\Output;

use App;
use App\Utils\DateTime;
use Throwable;

class CliOutput
{
    private function __construct()
    {
    }

    public static function message(
        string $text,
        bool $with_date = false,
        int $tabs = 0,
        Foreground $foreground = null,
        Background $background = null,
        bool $line_break = true,
    ): void
    {
        echo($with_date ? ('[' . (new DateTime)->format('Y.m.d H:i:s:u') . '] ') : ''),
        ($tabs === 0 ? '' : str_repeat('  ', $tabs)),
        self::formatString(
            $text,
            [
                $foreground?->value,
                $background?->value,
            ],
        ),
        ($line_break ? PHP_EOL : '');
    }

    public static function error(
        string $text,
        bool $with_date = false,
        int $tabs = 0,
        Foreground $foreground = null,
        Background $background = null,
        bool $line_break = true,
    ): void
    {
        $modifiers = [
            ($foreground ?? Foreground::Red)->value,
        ];

        if ($background !== null) {
            $modifiers[] = $background->value;
    }

        $string = ($tabs === 0 ? '' : str_repeat('  ', $tabs)) .
            ($with_date ? ('[' . (new DateTime)->format('Y.m.d H:i:s:u') . '] ') : '') .
            self::formatString($text, $modifiers) .
            ($line_break ? PHP_EOL : '');
        $string = App::filterError($string);
        fwrite(STDERR, $string);
    }

    public static function exception(
        Throwable $exception,
        bool $with_date = false,
        int $tabs = 0,
        Foreground $foreground = null,
        Background $background = null,
    ): void
    {
        $text = sprintf(
            "%s #%d: %s in %s:%d\n%s",
            get_class($exception),
            $exception->getCode(),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString(),
        );

        self::error($text, $with_date, $tabs, $foreground, $background);
        if ($exception->getPrevious() !== null) {
            self::exception($exception->getPrevious(), $with_date, $tabs + 1, $foreground, $background);
        }
    }

    /**
     * @param int[] $modifiers
     */
    public static function formatString(string $string, array $modifiers): string
    {
        $modifiers_string = '';
        foreach ($modifiers as $modifier) {
            if ($modifier === null) {
                continue;
            }

            $modifiers_string .= "\033[{$modifier}m";
        }

        return $modifiers_string === ''
            ? $string
            : "$modifiers_string$string\033[0m";
    }

}