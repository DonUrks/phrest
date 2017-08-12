<?php

namespace Phrest\API;


interface HateoasResponseGeneratorAwareInterface
{
    public function setHateoasResponseGenerator(HateoasResponseGenerator $hateoasResponseGenerator);
}