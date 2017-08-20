<?php

namespace Phrest\API;

use PHPUnit\Framework\TestCase;

class ErrorCodesTestOwn extends ErrorCodes
{
    const MY_OWN_CODE_1 = self::LAST_PHREST_ERROR_CODE + 1;
    const MY_OWN_CODE_2 = self::LAST_PHREST_ERROR_CODE + 2;
}

class ErrorCodesTest extends TestCase
{
    static private $errorCodes = [
        'UNKNOWN' => 0,
        'INTERNAL_SERVER_ERROR' => 1,
        'JSON_DECODE_ERROR' => 2,
        'JSON_DECODE_ERROR_DEPTH' => 3,
        'JSON_DECODE_ERROR_STATE_MISMATCH' => 4,
        'JSON_DECODE_ERROR_CTRL_CHAR' => 5,
        'JSON_DECODE_ERROR_SYNTAX' => 6,
        'JSON_DECODE_ERROR_UTF8' => 7,
        'JSON_DECODE_ERROR_RECURSION' => 8,
        'JSON_DECODE_ERROR_INF_OR_NAN' => 9,
        'JSON_DECODE_ERROR_UNSUPPORTED_TYPE' => 10,
        'REQUEST_VALIDATION_ADDITIONAL_ITEMS' => 11,
        'REQUEST_VALIDATION_ADDITIONAL_PROP' => 12,
        'REQUEST_VALIDATION_ALL_OF' => 13,
        'REQUEST_VALIDATION_ANY_OF' => 14,
        'REQUEST_VALIDATION_DEPENDENCIES' => 15,
        'REQUEST_VALIDATION_DISALLOW' => 16,
        'REQUEST_VALIDATION_DIVISIBLE_BY' => 17,
        'REQUEST_VALIDATION_ENUM' => 18,
        'REQUEST_VALIDATION_EXCLUSIVE_MAXIMUM' => 19,
        'REQUEST_VALIDATION_EXCLUSIVE_MINIMUM' => 20,
        'REQUEST_VALIDATION_FORMAT' => 21,
        'REQUEST_VALIDATION_MAXIMUM' => 22,
        'REQUEST_VALIDATION_MAX_ITEMS' => 23,
        'REQUEST_VALIDATION_MAX_LENGTH' => 24,
        'REQUEST_VALIDATION_MAX_PROPERTIES' => 25,
        'REQUEST_VALIDATION_MINIMUM' => 26,
        'REQUEST_VALIDATION_MIN_ITEMS' => 27,
        'REQUEST_VALIDATION_MIN_LENGTH' => 28,
        'REQUEST_VALIDATION_MIN_PROPERTIES' => 29,
        'REQUEST_VALIDATION_MISSING_MAXIMUM' => 30,
        'REQUEST_VALIDATION_MISSING_MINIMUM' => 31,
        'REQUEST_VALIDATION_MULTIPLE_OF' => 32,
        'REQUEST_VALIDATION_NOT' => 33,
        'REQUEST_VALIDATION_ONE_OF' => 34,
        'REQUEST_VALIDATION_PATTERN' => 35,
        'REQUEST_VALIDATION_PREGEX' => 36,
        'REQUEST_VALIDATION_REQUIRED' => 37,
        'REQUEST_VALIDATION_REQUIRES' => 38,
        'REQUEST_VALIDATION_SCHEMA' => 39,
        'REQUEST_VALIDATION_TYPE' => 40,
        'REQUEST_VALIDATION_UNIQUE_ITEMS' => 41,
        'REQUEST_PARAMETER_VALIDATION' => 42,
        'PATH_NOT_FOUND' => 43
    ];

    public function testConstructor()
    {
        $errorCodes = new ErrorCodes();
        self::assertInstanceOf(ErrorCodes::class, $errorCodes);
    }

    public function testLAST_PHREST_ERROR_CODE()
    {
        self::assertEquals(1000, ErrorCodes::LAST_PHREST_ERROR_CODE);
    }

    public function testGetErrorCodes()
    {
        $errorCodes = new ErrorCodes();
        $errorCodesArray = $errorCodes->getErrorCodes();

        self::assertEquals(self::$errorCodes, $errorCodesArray);
    }

    public function testGetErrorCodesExtended()
    {
        $errorCodes = new ErrorCodesTestOwn();
        $errorCodesArray = $errorCodes->getErrorCodes();

        self::assertEquals(
            self::$errorCodes + [
                'MY_OWN_CODE_1' => 1001,
                'MY_OWN_CODE_2' => 1002
            ], $errorCodesArray
        );
    }
}
