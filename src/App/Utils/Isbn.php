<?php

declare(strict_types=1);

namespace App\Utils;

use Biblys\Isbn\Isbn as BiblysIsbn;
use Biblys\Isbn\IsbnValidationException;
use Exception;

readonly class Isbn
{
    private function __construct(
        public string $value,
    )
    {

    }

    public static function tryFromString(string $string): ?self
    {
        $string = self::filterString($string);

        try {
            BiblysIsbn::validateAsIsbn13($string);
            return new self($string);
        } catch(Exception) {

        }

        return null;
    }

    /**
     * @throws IsbnValidationException
     */
    public static function fromString(string $string): self
    {
        $string = self::filterString($string);
        BiblysIsbn::validateAsIsbn13($string);
        return new self($string);
    }

    protected static function filterString(string $string): string
    {
        return preg_replace('|\s-\.|', '', $string);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}