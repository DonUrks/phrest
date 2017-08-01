<?php
namespace Phrest\API;

class HateoasResponseGenerator
{
    private $hateoas;

    public function __construct(\Hateoas\Hateoas $hateoas)
    {
        $this->hateoas = $hateoas;
    }

    public function generate($model, int $httpStatusCode = 200, array $headers = []) : \Psr\Http\Message\ResponseInterface {
        return new \Zend\Diactoros\Response\TextResponse(
            $this->hateoas->serialize($model, 'json'),
            $httpStatusCode,
            $headers + ['Content-Type' => 'application/json']
        );
    }
}