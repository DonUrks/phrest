<?php
namespace Phrest\API\Action;

class Swagger implements \Interop\Http\ServerMiddleware\MiddlewareInterface
{
    /**
     * @var string
     */
    private $swagger;

    public function __construct(string $swagger)
    {
        $this->swagger = $swagger;
    }

    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        return new \Zend\Diactoros\Response\TextResponse($this->swagger, 200, ['Content-Type' => 'application/json']);
    }
}