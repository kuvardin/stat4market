<?php

declare(strict_types=1);

namespace App\Sessions;

use RuntimeException;

class IpAddress
{
    private const IP_V4_HEX_PREFIX = '000000000000000000000000';
    private const IP_V4_INT_MAX = 4294967295;

    readonly public ?int $ip_v4;
    readonly public ?string $ip_v6;

    private ?string $ip_v4_formatted = null;
    private ?string $ip_v6_formatted = null;

    private function __construct(?int $ip_v4, ?string $ip_v6)
    {
        if ($ip_v4 !== null && $ip_v6 !== null) {
            throw new RuntimeException("IP v4 and v6 cannot be not empty both");
        }

        if ($ip_v4 === null && $ip_v6 === null) {
            throw new RuntimeException("IP v4 and v6 cannot be empty both");
        }

        $this->ip_v4 = $ip_v4;
        $this->ip_v6 = $ip_v6;
    }

    public static function makeByValue(string $value): ?self
    {
        if (!filter_var($value, FILTER_VALIDATE_IP)) {
            return null;
        }

        $ip_v6 = null;
        $ip_v4 = ip2long($value);
        if ($ip_v4 === false) {
            $ip_v4 = null;

            $ip_v6_binary = inet_pton($value);
            if ($ip_v6_binary === false) {
                throw new RuntimeException("Error converting IPv6 to binary: $value");
            }

            $ip_v6 = bin2hex($ip_v6_binary);
        }

        if ($ip_v4 !== null || $ip_v6 !== null) {
            return new self($ip_v4, $ip_v6);
        }

        return null;
    }

    public static function requireByHex(string $hex): self
    {
        if (!preg_match('|^[0-9a-f]{32}$|', $hex)) {
            throw new RuntimeException("Incorrect hex IP: $hex");
        }

        if (str_starts_with($hex, self::IP_V4_HEX_PREFIX)) {
            $ip_v4 = hexdec(substr($hex, 24));
            return new self($ip_v4, null);
        }

        return new self(null, $hex);
    }

    public static function requireByValue(string $value): self
    {
        $result = self::makeByValue($value);
        if ($result === null) {
            throw new RuntimeException("Incorrect IP: $value");
        }

        return $result;
    }

    public static function makeFromServer(array $server): ?self
    {
        $values = [];

        if (!empty($server["HTTP_CF_CONNECTING_IP"])) {
            $values[] = $server["HTTP_CF_CONNECTING_IP"];
        } elseif (!empty($server["HTTP_CLIENT_IP"])) {
            $values[] = $server["HTTP_CLIENT_IP"];
        }

        if (!empty($server['HTTP_X_FORWARDED_FOR'])) {
            $values[] = $server['HTTP_X_FORWARDED_FOR'];
        }

        if (!empty($server['REMOTE_ADDR'])) {
            $values[] = $server['REMOTE_ADDR'];
        }

        foreach ($values as $value) {
            $ip_address = IpAddress::makeByValue($value);
            if ($ip_address !== null) {
                return $ip_address;
            }
        }

        return null;
    }

    public function getFormatted(): string
    {
        if ($this->ip_v4 !== null) {
            return $this->ip_v4_formatted ??= long2ip($this->ip_v4);
        }

        return $this->ip_v6_formatted ??= inet_ntop(hex2bin($this->ip_v6));
    }

    public function getHexString(): string
    {
        if ($this->ip_v6 !== null) {
            return $this->ip_v6;
        }

        return self::IP_V4_HEX_PREFIX . sprintf('%08x', $this->ip_v4);
    }

    public function getIpV4Next(int $increment = 1): ?self
    {
        if ($this->ip_v4 === null) {
            return null;
        }

        $next_ip_v4_value = $this->ip_v4 + $increment;
        return $next_ip_v4_value <= self::IP_V4_INT_MAX && $next_ip_v4_value >= 0
            ? new self($next_ip_v4_value, null)
            : null;
    }
}