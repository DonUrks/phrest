<?php

namespace Phrest\API;


class RequestSwaggerData
{
    private $bodyValue;
    private $queryValues;
    private $pathValues;
    private $headerValues;

    public function __construct(
        ?\stdClass $bodyValue,
        array $queryValues,
        array $pathValues,
        array $headerValues
    )
    {
        $this->bodyValue = $bodyValue;
        $this->queryValues = $queryValues;
        $this->pathValues = $pathValues;
        $this->headerValues = $headerValues;
    }

    public function getBodyValue(): \stdClass
    {
        return $this->bodyValue ?? new \stdClass();
    }

    public function getQueryValues(): array
    {
        return $this->queryValues;
    }

    public function getPathValues(): array
    {
        return $this->pathValues;
    }

    public function getHeaderValues(): array
    {
        return $this->headerValues;
    }
}