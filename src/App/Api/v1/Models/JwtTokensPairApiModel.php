<?php

declare(strict_types=1);

namespace App\Api\v1\Models;

use App\Api\v1\ApiModelImmutable;
use App\Api\v1\Output\ApiField;
use App\Jwt\TokensPair;

class JwtTokensPairApiModel extends ApiModelImmutable
{
    public function __construct(
        protected TokensPair $tokens,
    )
    {

    }

    public static function getDescription(): ?string
    {
        return 'Пара JWT-токенов';
    }

    public static function getFields(): array
    {
        return [
            'access_token' => ApiField::object(JwtTokenApiModel::class, false, 'Токен для доступа'),
            'refresh_token' => ApiField::object(JwtTokenApiModel::class, false, 'Токен для обновления'),
        ];
    }

    public function getPublicData(): array
    {
        return [
            'access_token' => new JwtTokenApiModel($this->tokens->access_token),
            'refresh_token' => new JwtTokenApiModel($this->tokens->refresh_token),
        ];
    }
}