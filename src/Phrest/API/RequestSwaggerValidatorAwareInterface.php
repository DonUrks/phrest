<?php

namespace Phrest\API;


interface RequestSwaggerValidatorAwareInterface
{
    public function setRequestSwaggerValidator(RequestSwaggerValidator $requestSwaggerValidator);
}