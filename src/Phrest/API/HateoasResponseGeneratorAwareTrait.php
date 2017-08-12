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
}