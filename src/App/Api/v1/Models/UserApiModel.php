<?php

declare(strict_types=1);

namespace App\Api\v1\Models;

use App\Actions\Action;
use App\Api\v1\ApiModelMutable;
use App\Api\v1\Output\ApiField;
use App\Sessions\Session;
use App\Users\User;

class UserApiModel extends ApiModelMutable
{
    public function __construct(
        protected User $user,
    )
    {

    }

    public static function getDescription(): ?string
    {
        return 'Пользователь';
    }

    public static function getFields(): array
    {
        return [
            'id' => ApiField::integer(false, 'ID'),
            'phone_number' => ApiField::string(true, 'Номер телефона'),
            'username' => ApiField::string(true, 'Юзернейм'),
            'first_name' => ApiField::string(false, 'Имя'),
            'last_name' => ApiField::string(true, 'Фамилия'),
            'middle_name' => ApiField::string(true, 'Отчество'),
            'last_request_date' => ApiField::timestamp(true, 'Дата последнего посещения'),
            'is_online' => ApiField::boolean(false, 'Флаг "Онлайн"'),
        ];
    }

    public function getPublicData(Session $session): array
    {
        $can_show = $session->getUserId() === $this->user->getId()
            || $session->can(Action::CLASS_PRIVATE_USERS_INFO, Action::SHOW);

        return [
            'id' => $this->user->getId(),
            'phone_number' => $can_show ? $this->user->getPhoneNumber() : null,
            'first_name' => $this->user->getFirstName(),
            'last_name' => $can_show ? $this->user->getLastName() : null,
            'middle_name' => $can_show ? $this->user->getMiddleName() : null,
            'last_request_date' => $this->user->getLastRequestDate(),
            'is_online' => $this->user->isOnline(),
        ];
    }
}