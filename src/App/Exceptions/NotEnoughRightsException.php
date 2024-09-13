<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class NotEnoughRightsException extends Exception
{
    readonly string $class;
    readonly int $actions;

    public function __construct(string $class, int $actions)
    {
        $this->class = $class;
        $this->actions = $actions;
        $message = "Not enough rights for class $class and actions $actions";
        parent::__construct($message);
    }
}