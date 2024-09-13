<?php

declare(strict_types=1);

namespace App\Site\Input;

class SiteField
{
    public function __construct(
        readonly public SiteFieldType $type,
        readonly public bool $from_post = false,
        readonly public ?string $description = null,
    )
    {

    }
}