<?php

declare(strict_types=1);

namespace App\Users;

use App\Telegram\Users\BotUser;
use RuntimeException;

readonly class Person
{
    public function __construct(
        public ?User $user,
        public ?BotUser $bot_user,
    )
    {
        if ($this->user === null && $this->bot_user !== null) {
            throw new RuntimeException('Empty person');
        }
    }
}