<?php
namespace Phrest\API\Action;

class Swagger implements \Interop\Http\ServerMiddleware\MiddlewareInterface
{
    /**
     * @var \Phrest\Swagger
     */
    private $swagger;

    public function __construct(\Phrest\Swagger $swagger)
    {
        $this->swagger = $swagger;
    }

    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        return new \Zend\Diactoros\Response\TextResponse((string) $this->swagger, 200, ['Content-Type' => 'application/json']);
    }
}