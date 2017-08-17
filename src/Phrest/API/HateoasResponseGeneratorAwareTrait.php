<?php

namespace Phrest\API;


trait HateoasResponseGeneratorAwareTrait
{
    /**
     * @var HateoasResponseGenerator
     */
    protected $hateoasResponseGenerator;

    public function setHateoasResponseGenerator(HateoasResponseGenerator $hateoasResponseGenerator)
    {
        $this->hateoasResponseGenerator = $hateoasResponseGenerator;
    }

    /**
     * @param $data
     * @param int $httpStatusCode
     * @param array $headers
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function generateHateoasResponse($data, int $httpStatusCode = 200, array $headers = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->hateoasResponseGenerator->generate($data, $httpStatusCode, $headers);
    }
}
