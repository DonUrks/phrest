<?php
namespace Phrest\API;

class ErrorEntry
{
    private $code;
    private $field;
    private $message;
    private $constraint;

    public function __construct(int $code, string $field, string $message, string $constraint)
    {
        $this->code = $code;
        $this->field = $field;
        $this->message = $message;
        $this->constraint = $constraint;
    }

    public function code() : int {
        return $this->code;
    }

    public function field() : string {
        return $this->field;
    }

    public function message() : string {
        return $this->message;
    }

    public function constraint() : string {
        return $this->constraint;
    }
}