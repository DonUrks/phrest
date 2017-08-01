<?php
namespace Phrest\API;

abstract class ErrorCode
{
    const UNKNOWN = 0;

    const INTERNAL_SERVER_ERROR = 1;

    const JSON_DECODE_ERROR = 2;
    const JSON_DECODE_ERROR_DEPTH = 3;
    const JSON_DECODE_ERROR_STATE_MISMATCH = 4;
    const JSON_DECODE_ERROR_CTRL_CHAR = 5;
    const JSON_DECODE_ERROR_SYNTAX = 6;
    const JSON_DECODE_ERROR_UTF8 = 7;
    const JSON_DECODE_ERROR_RECURSION = 8;
    const JSON_DECODE_ERROR_INF_OR_NAN = 9;
    const JSON_DECODE_ERROR_UNSUPPORTED_TYPE = 10;

    const PATH_NOT_FOUND = 11;


}