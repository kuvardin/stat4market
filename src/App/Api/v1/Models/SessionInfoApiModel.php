<?php

declare(strict_types=1);

namespace App\Api\v1\Models;

use App\Api\v1\ApiModelMutable;
use App\Api\v1\Output\ApiField;
use App\Jwt\TokensPair;
use App\Sessions\Session;

class SessionInfoApiModel extends ApiModelMutable
{
    public function __construct(
        protected Session $session,
        protected ?TokensPair $tokens_pair = null,
    )
    {

    }

    public static function getDescription(): ?string
    {
        return 'Сессия';
    }

    public static function getFields(): array
    {
        return [
            'user' => ApiField::object(UserApiModel::class, true, 'Пользователь'),
            'session' => ApiField::object(SessionApiModel::class, false, 'Сессия'),
            'tokens' => ApiField::object(JwtTokensPairApiModel::class, true, 'JWT-токены'),
            'timezone' => ApiField::string(false, 'Временная зона'),
        ];
    }

    public function getPublicData(Session $session): array
    {
        return [
            'user' => $this->session->getUser() === null ? null : new UserApiModel($this->session->getUser()),
            'session' => new SessionApiModel($this->session),
            'tokens' => $this->tokens_pair === null ? null : new JwtTokensPairApiModel($this->tokens_pair),
            'timezone' => $this->session->getDateTimeZone()->getName(),
        ];
    }
}