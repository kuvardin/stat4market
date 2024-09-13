<?php

declare(strict_types=1);

namespace App\Jwt;

class Payload
{
    protected const FIELD_TYPE = 't';
    protected const FIELD_SESSION_ID = 's';
    protected const FIELD_AUTHORIZATION_ID = 'a';
    protected const FIELD_USER_ID = 'u';
    protected const FIELD_EXPIRATION_DATE = 'e';

    public function __construct(
        readonly TokenType $type,
        readonly public int $session_id,
        readonly public ?int $authorization_id,
        readonly public ?int $user_id,
        readonly public int $expiration_date,
    )
    {

    }

    public static function makeFromArray(array $data): ?self
    {
        if (!isset($data[self::FIELD_TYPE]) || !is_string($data[self::FIELD_TYPE])) {
            return null;
        }

        $type = TokenType::tryFrom($data[self::FIELD_TYPE]);
        if ($type === null) {
            return null;
        }

        if (!isset($data[self::FIELD_SESSION_ID]) || !is_int($data[self::FIELD_SESSION_ID])) {
            return null;
        }

        if (!isset($data[self::FIELD_AUTHORIZATION_ID]) || !is_int($data[self::FIELD_AUTHORIZATION_ID])) {
            return null;
        }

        if (!isset($data[self::FIELD_USER_ID]) || !is_int($data[self::FIELD_USER_ID])) {
            return null;
        }

        if (!isset($data[self::FIELD_EXPIRATION_DATE]) || !is_int($data[self::FIELD_EXPIRATION_DATE])) {
            return null;
        }

        return new self(
            type: $type,
            session_id: $data[self::FIELD_SESSION_ID],
            authorization_id: $data[self::FIELD_AUTHORIZATION_ID],
            user_id: $data[self::FIELD_USER_ID],
            expiration_date: $data[self::FIELD_EXPIRATION_DATE],
        );
    }

    public function getArray(): array
    {
        return [
            self::FIELD_TYPE => $this->type->value,
            self::FIELD_SESSION_ID => $this->session_id,
            self::FIELD_AUTHORIZATION_ID => $this->authorization_id,
            self::FIELD_USER_ID => $this->user_id,
            self::FIELD_EXPIRATION_DATE => $this->expiration_date,
        ];
    }

    public function isExpired(int $current_timestamp = null): bool
    {
        return $this->expiration_date < ($current_timestamp ?? time());
    }
}