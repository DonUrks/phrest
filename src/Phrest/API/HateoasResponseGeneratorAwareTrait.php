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
     * Generates a JSON HATEOAS response based on the given data and annotations.
     * @param $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function generateHateoasResponse($data): \Psr\Http\Message\ResponseInterface
    {
        return $this->hateoasResponseGenerator->generate($data);
    }
}
