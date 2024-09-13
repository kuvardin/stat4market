<?php

declare(strict_types=1);

namespace App\Web;

use App\Jwt\Token;
use App\Sessions\IpAddress;
use App\Sessions\UserAgent;
use JsonException;

readonly class WebRequest
{
    public array $route_parts;

    public function __construct(
        public string $method,
        public array $get,
        public array $post,
        public IpAddress $ip_address,
        public ?UserAgent $user_agent,
        public array $cookies,
        public string $route,
        public ?string $input_string,
        public ?string $referrer,
        public ?Token $token,
    )
    {
        $this->route_parts = explode('/', trim($this->route, '/'));
    }

    public function getJsonDecodedInput(): mixed
    {
        try {
            return $this->input_string === null
                ? null
                : json_decode($this->input_string, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }
}