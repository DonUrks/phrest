<?php

namespace Phrest\Swagger;


class Parameters
{
    private $bodyParameter;
    private $queryParameters;
    private $headerParameters;
    private $pathParameters;

    public function __construct(array $bodyParameter, array $queryParameters, array $headerParameters, array $pathParameters)
    {
        $this->bodyParameter = $bodyParameter;
        $this->queryParameters = $queryParameters;
        $this->headerParameters = $headerParameters;
        $this->pathParameters = $pathParameters;
    }

    public function getBodyParameter(): array
    {
        return $this->bodyParameter;
    }

    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    public function getHeaderParameters(): array
    {
        return $this->headerParameters;
    }

    public function getPathParameters(): array
    {
        return $this->pathParameters;
    }
}