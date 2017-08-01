<?php
namespace Phrest\API;

class Error
{
    private $code;
    private $message;
    private $errors;

    public function __construct(int $code, string $message, \Phrest\API\ErrorEntry ...$errorEntries)
    {
        $this->code = $code;
        $this->message = $message;
        $this->errors = $errorEntries;
    }

    public function code() : int {
        return $this->code;
    }

    public function message() : string {
        return $this->message;
    }

    public function errors() : array {
        return $this->errors;
    }
}