<?php

namespace Phrest\API;

use PHPUnit\Framework\TestCase;

class RequestSwaggerValidatorAwareTraitTest extends TestCase
{
    use RequestSwaggerValidatorAwareTrait;

    public function testSetRequestSwaggerValidator()
    {
        $swaggerValidatorMock = $this->createMock(\Phrest\API\RequestSwaggerValidator::class);
        $this->setRequestSwaggerValidator($swaggerValidatorMock);
        self::assertInstanceOf(RequestSwaggerValidator::class, $this->requestSwaggerValidator);
    }
}