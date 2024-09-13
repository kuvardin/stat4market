<?php

declare(strict_types=1);

namespace App\Sessions;

trait IpAddressRequiredTrait
{
    protected string $ip_address_hex;

    public function getIpAddressHex(): string
    {
        return $this->ip_address_hex;
    }

    public function getIpAddress(): IpAddress
    {
        return IpAddress::requireByHex($this->ip_address_hex);
    }
}