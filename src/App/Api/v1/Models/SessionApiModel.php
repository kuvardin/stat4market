<?php

declare(strict_types=1);

namespace App\Api\v1\Models;

use App\Api\v1\ApiModelMutable;
use App\Api\v1\Output\ApiField;
use App\Sessions\Session;

class SessionApiModel extends ApiModelMutable
{
    public function __construct(
        protected Session $session,
    )
    {
    }

    public static function getDescription(): ?string
    {
        return 'Данные о сессии';
    }

    public static function getFields(): array
    {
        return [
            'language_code' => ApiField::string(false, 'Код языка'),
        ];
    }

    public function getPublicData(Session $session): array
    {
        return [
            'language_code' => $this->session->getLocale()->value,
        ];
    }
}