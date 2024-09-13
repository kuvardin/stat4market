<?php

declare(strict_types=1);

namespace App\Jwt;

use App;
use App\Sessions\Session;

class TokensPair
{
    private function __construct(
        readonly public Token $access_token,
        readonly public Token $refresh_token,
    )
    {
    }

    public static function create(Session $session, int $current_timestamp = null): self
    {
        $current_timestamp ??= time();
        return new self(
            access_token: Token::create(
                new Payload(
                    type: TokenType::Access,
                    session_id: $session->getId(),
                    authorization_id: $session->getAuthorizationId(),
                    user_id: $session->getUserId(),
                    expiration_date: $current_timestamp + App::settings('jwt.ttl.access_token'),
                ),
            ),
            refresh_token: Token::create(
                new Payload(
                    type: TokenType::Refresh,
                    session_id: $session->getId(),
                    authorization_id: $session->getAuthorizationId(),
                    user_id: $session->getUserId(),
                    expiration_date: $current_timestamp + App::settings('jwt.ttl.refresh_token'),
                ),
            )
        );
    }

}