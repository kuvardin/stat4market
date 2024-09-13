<?php

declare(strict_types=1);

namespace App\Api\v1\Models;

use App\Api\v1\ApiModelImmutable;
use App\Api\v1\Output\ApiField;
use App\Jwt\Token;

class JwtTokenApiModel extends ApiModelImmutable
{
    public function __construct(
        protected Token $token,
    )
    {

    }

    public static function getDescription(): ?string
    {
        return 'JWT-токен';
    }

    public static function getFields(): array
    {
        return [
            'value' => ApiField::string(false, 'Значение токена'),
            'expiration_date' => ApiField::timestamp(false, 'Дата истечения'),
        ];
    }

    public function getPublicData(): array
    {
        return [
            'value' => $this->token->getValue(),
            'expiration_date' => $this->token->payload->expiration_date,
        ];
    }
}