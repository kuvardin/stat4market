<?php

declare(strict_types=1);

namespace App\Sessions;

trait IpAddressTrait
{
    protected ?string $ip_address_hex;

    public function getIpAddressHex(): ?string
    {
        return $this->ip_address_hex;
    }

    public function getIpAddress(): ?IpAddress
    {
        return $this->ip_address_hex === null ? null : IpAddress::requireByHex($this->ip_address_hex);
    }
}