<?php

declare(strict_types=1);

namespace App\Jwt;

enum TokenType: string
{
    case Access = 'access';
    case Refresh = 'refresh';
}