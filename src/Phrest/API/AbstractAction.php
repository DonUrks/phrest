<?php

namespace Phrest\API;

abstract class AbstractAction implements
    \Interop\Http\ServerMiddleware\MiddlewareInterface,
    \Psr\Log\LoggerAwareInterface
{
    use RESTActionTrait;

    protected function onRESTRequest(\Psr\Http\Message\ServerRequestInterface $request, string $method): \Psr\Http\Message\ResponseInterface
    {
        $response = call_user_func_array([$this, $method], [$request]);

        // if there is no response -> its a delete request without returning a response (=204 No Content)
        return $response ?? new \Zend\Diactoros\Response\EmptyResponse();
    }

    public function get(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed('GET');
    }

    public function post(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed('POST');
    }

    public function put(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed('PUT');
    }

    public function patch(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed('PATCH');
    }

    public function delete(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed('DELETE');
    }
}
