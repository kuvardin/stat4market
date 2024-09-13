<?php

declare(strict_types=1);

namespace App\Cli\Output;

enum Foreground: int
{
    case Black = 30;
    case BlackBright = 90;
    case Red = 31;
    case RedBright = 91;
    case Green = 32;
    case GreenBright = 92;
    case Yellow = 33;
    case YellowBright = 93;
    case Blue = 34;
    case BlueBright = 94;
    case Magenta = 35;
    case MagentaBright = 95;
    case Cyan = 36;
    case CyanBright = 96;
    case White = 37;
    case WhiteBright = 97;
}