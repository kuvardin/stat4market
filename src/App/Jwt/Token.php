<?php

declare(strict_types=1);

namespace App\Jwt;

use App;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

class Token
{
    protected const ALGO = 'HS256';

    private function __construct(
        readonly public Payload $payload,
        protected ?string $value = null,
    )
    {

    }

    public static function create(Payload $payload): self
    {
        return new self($payload);
    }

    public static function decode(string $value): ?self
    {
        try {
            $decoded = JWT::decode($value, new Key(App::settings('jwt.key'), self::ALGO));
            $payload = Payload::makeFromArray((array)$decoded);
            if ($payload !== null) {
                return new self($payload, $value);
            }
        } catch (SignatureInvalidException) {

        }

        return null;
    }

    public static function makeFromHttpHeader(string $http_header_value): ?self
    {
        if (preg_match('|^Bearer\s+(.*)$|i', $http_header_value, $match)) {
            $token_value = trim($match[1]);
            if ($token_value !== '') {
                return self::decode($token_value);
            }
        }

        return null;
    }

    public function getValue(): string
    {
        return $this->value ??= JWT::encode($this->payload->getArray(), App::settings('jwt.key'), self::ALGO);
    }
}