<?php

namespace Phrest;

use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testConstructor()
    {
        $e = new Exception();
        self::assertInstanceOf(Exception::class, $e);
    }
}
