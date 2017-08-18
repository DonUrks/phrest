<?php

namespace Phrest\API;

abstract class AbstractSwaggerValidatorAction implements
    \Interop\Http\ServerMiddleware\MiddlewareInterface,
    \Psr\Log\LoggerAwareInterface,
    RequestSwaggerValidatorAwareInterface
{
    use RequestSwaggerValidatorAwareTrait;
    use RESTActionTrait;

    protected function onRESTRequest(\Psr\Http\Message\ServerRequestInterface $request, string $method): \Psr\Http\Message\ResponseInterface
    {
        /** @var \Zend\Expressive\Router\RouteResult $routingResult */
        $routingResult = $request->getAttribute(\Zend\Expressive\Router\RouteResult::class);
        if (!($routingResult instanceof \Zend\Expressive\Router\RouteResult)) {
            throw new \Phrest\Exception('request attribute "' . \Zend\Expressive\Router\RouteResult::class . '" is not a RouteResult instance');
        }
        $route = $routingResult->getMatchedRoute();
        if (!($route instanceof \Zend\Expressive\Router\Route)) {
            throw new \Phrest\Exception('no matched route found');
        }

        $operationId = $method.'.'.$route->getName();
        $operationIds = $route->getOptions()['operationIds'] ?? [];
        if(array_key_exists($method, $operationIds)) {
            $operationId = $operationIds[$method];
        }

        $data = $this->requestSwaggerValidator->validate($request, $operationId);

        $response = call_user_func_array([$this, $method], [$data]);

        // if there is no response -> create a response (=204 No Content)
        return $response ?? new \Zend\Diactoros\Response\EmptyResponse();
    }

    public function get(RequestSwaggerData $data): ?\Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed('GET');
    }

    public function post(RequestSwaggerData $data): ?\Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed('POST');
    }

    public function put(RequestSwaggerData $data): ?\Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed('PUT');
    }

    public function patch(RequestSwaggerData $data): ?\Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed('PATCH');
    }

    public function delete(RequestSwaggerData $data): ?\Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed('DELETE');
    }
}
