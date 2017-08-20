<?php

namespace Phrest\API;

use PHPUnit\Framework\TestCase;

class ErrorEntryTest extends TestCase
{
    public function testConstructor()
    {
        $errorEntry = new ErrorEntry(
            1234,
            'some field',
            'some message',
            'some constraint'
        );

        self::assertEquals(1234, $errorEntry->code());
        self::assertEquals('some field', $errorEntry->field());
        self::assertEquals('some message', $errorEntry->message());
        self::assertEquals('some constraint', $errorEntry->constraint());
    }
}