<?php

declare(strict_types=1);

namespace App\Sessions;

enum OperationSystem: string
{
    case Android = 'Android';
    case IOS = 'iOS';
    case Linux = 'Linux';
    case Windows = 'Windows';
    case MacOS = 'MacOS';
}