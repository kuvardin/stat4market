<?php

declare(strict_types=1);

namespace App\Web;

readonly class WebSite
{
    public function __construct(
        public int $id,
        public string $host,
        public string $cookies_domain,
    )
    {

    }
}