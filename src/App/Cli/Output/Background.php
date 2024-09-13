<?php

declare(strict_types=1);

namespace App\Cli\Output;

enum Background: int
{
    case Black = 40;
    case BlackBright = 100;
    case Red = 41;
    case RedBright = 101;
    case Green = 42;
    case GreenBright = 102;
    case Yellow = 43;
    case YellowBright = 103;
    case Blue = 44;
    case BlueBright = 104;
    case Magenta = 45;
    case MagentaBright = 105;
    case Cyan = 46;
    case CyanBright = 106;
    case White = 47;
    case WhiteBright = 107;
}