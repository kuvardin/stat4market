<?php

declare(strict_types=1);

namespace test;

use App\Utils\Isbn;
use PHPUnit\Framework\TestCase;

final class IsbnTest extends TestCase
{
    public function testIsbn(): void
    {
        $this->assertNotNull(Isbn::tryFromString('978-5-17-108154-6'));
        $this->assertNull(Isbn::tryFromString('978-5-17-108154-7'));
    }
}