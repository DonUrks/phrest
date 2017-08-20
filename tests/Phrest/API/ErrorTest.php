<?php

namespace Phrest\API;

use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    public function testConstructor()
    {
        $errorEntry1 = new ErrorEntry(
            1,
            'error entry 1',
            'some message',
            'some constraint'
        );
        $errorEntry2 = new ErrorEntry(
            2,
            'error entry 2',
            'some message',
            'some constraint'
        );

        $error = new Error(
            1234,
            'some error message',
            $errorEntry1,
            $errorEntry2
        );

        self::assertEquals(1234, $error->code());
        self::assertEquals('some error message', $error->message());

        $errorEntries = $error->errors();
        self::assertCount(2, $errorEntries);
        self::assertTrue(spl_object_hash($errorEntry1) === spl_object_hash($errorEntries[0]));
        self::assertTrue(spl_object_hash($errorEntry2) === spl_object_hash($errorEntries[1]));
    }
}
