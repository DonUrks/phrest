<?php

namespace Phrest\API;


trait RequestSwaggerValidatorAwareTrait
{
    /**
     * @var RequestSwaggerValidator
     */
    protected $requestSwaggerValidator;

    public function setRequestSwaggerValidator(RequestSwaggerValidator $requestSwaggerValidator)
    {
        $this->requestSwaggerValidator = $requestSwaggerValidator;
    }
}