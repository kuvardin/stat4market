<?php

declare(strict_types=1);

namespace App\Site\Input;

enum SiteFieldType
{
    case Integer;
    case Float;
    case String;
    case Boolean;
    case DateTime;
    case DateRange;
    case Phrase;
    case File;

    case Array;
    case ArrayOfInteger;
    case ArrayOfString;
    case ArrayOfFloat;
    case ArrayOfFile;
    case ArrayOfDateTime;
}