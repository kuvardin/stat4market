<?php

declare(strict_types=1);

namespace App\Utils;

use DateTimeZone;

class DateTime extends \DateTime
{
    private static ?int $cached_timestamp = null;
    private static ?DateTimeZone $timezone = null;

    public function __construct(string $time = null, DateTimeZone $timezone = null)
    {
        $timezone ??= self::$timezone ??= new DateTimeZone(date_default_timezone_get());
        parent::__construct($time ?? 'now');
        $this->setTimezone($timezone);
    }

    public static function makeByTimestamp(int $timestamp, DateTimeZone $timezone = null): self
    {
        $timezone ??= self::$timezone ??= new DateTimeZone(date_default_timezone_get());
        /** @noinspection PhpUnhandledExceptionInspection */
        $result = new self('@' . $timestamp, $timezone);
        $result->setTimezone($timezone);
        return $result;
    }

    public static function makeByString(string $time, DateTimeZone $time_zone = null): ?self
    {
        return new self($time, $time_zone);
    }

    public static function cacheTimestamp(int $timestamp = null): void
    {
        self::$cached_timestamp = $timestamp ?? time();
    }

    public static function getCachedTimestamp(): int
    {
        return self::$cached_timestamp;
    }

    public function getShorten(bool $with_seconds = false): string
    {
        if ($this->isToday()) {
            return $this->format($with_seconds ? 'H:i:s' : 'H:i');
        }

        return $this->format('Y.m.d');
    }

    public function isToday(): bool
    {
        return self::$cached_timestamp - $this->getTimestamp() <= 86400;
    }

    public function isPast(): bool
    {
        return self::$cached_timestamp - $this->getTimestamp() > 0;
    }

    public function isFuture(): bool
    {
        return self::$cached_timestamp - $this->getTimestamp() <= 0;
    }

    public function __toString(): string
    {
        return $this->format('Y.m.d H:i:s:u');
    }

    public static function today(DateTimeZone $time_zone = null): self
    {
        return (new self(null, $time_zone))->setTime(0, 0);
    }

    public static function yesterday(DateTimeZone $time_zone = null): self
    {
        return (new self(null, $time_zone))->modify('-1 day')->setTime(0, 0);
    }

    public static function makeByTimestampUtc(int $timestamp): self
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new self('@' . $timestamp, new DateTimeZone('UTC'));
    }

    public function formatForHtml(bool $with_time = null): string
    {
        return $with_time ? $this->format('Y-m-d\TH:i') : $this->format('Y-m-d');
    }

    public function withoutTime(): self
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new self($this->format('Y-m-d'), $this->getTimezone());
    }

    public function getAge(self $diff_date = null): int
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->diff($diff_date ?? (new self(null, $this->getTimezone())))->y;
    }

    public function getDayNumber(): int
    {
        return (int)$this->format('z');
    }

    public function getUtc(): self
    {
        return self::makeByTimestamp($this->getTimestamp(), new DateTimeZone('UTC'));
    }
}