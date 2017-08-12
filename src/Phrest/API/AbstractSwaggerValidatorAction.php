<?php

namespace Phrest\API;

abstract class AbstractSwaggerValidatorAction implements
    \Interop\Http\ServerMiddleware\MiddlewareInterface,
    \Psr\Log\LoggerAwareInterface,
    RequestSwaggerValidatorAwareInterface
{
    use RequestSwaggerValidatorAwareTrait;
    use \Psr\Log\LoggerAwareTrait;

    /**
     * @var array
     */
    static private $methods = [
        'get' => 'GET',
        'post' => 'POST',
        'put' => 'PUT',
        'delete' => 'DELETE',
        'patch' => 'PATCH',
        'options' => 'OPTIONS',
    ];

    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $method = $request->getMethod();

        $this->logger->debug(__METHOD__ . ' called', ['method' => $method]);

        if (!in_array($method, self::$methods)) {
            $this->throwMethodNotAllowed($method);
        }

        /** @var \Zend\Expressive\Router\RouteResult $routingResult */
        $routingResult = $request->getAttribute(\Zend\Expressive\Router\RouteResult::class);
        if(!($routingResult instanceof \Zend\Expressive\Router\RouteResult)) {
            throw new \Phrest\Exception('request attribute "'.\Zend\Expressive\Router\RouteResult::class.'" is not a RouteResult instance');
        }
        $route = $routingResult->getMatchedRoute();
        if(!($route instanceof \Zend\Expressive\Router\Route)) {
            throw new \Phrest\Exception('no matched route found');
        }

        $method = strtolower($method);

        $data = $this->requestSwaggerValidator->validate($request, $method, $route->getPath());
        return call_user_func_array([$this, $method], [$data]);
    }

    public function options(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        return new \Zend\Diactoros\Response\EmptyResponse(
            \Phrest\Http\StatusCodes::NO_CONTENT,
            [
                'Allow' => implode(', ', $this->getAllowedMethods())
            ]
        );
    }

    private function getAllowedMethods(): array
    {
        $allowedMethods = [];

        /* @todo: Reflections? */
        $ref = new \ReflectionClass($this);
        foreach ($ref->getMethods() as $methodRef) {
            if ($methodRef->class != self::class && array_key_exists($methodRef->name, self::$methods)) {
                $allowedMethods[] = self::$methods[$methodRef->name];
            }
        }

        return $allowedMethods;
    }

    private function throwMethodNotAllowed(string $method)
    {
        throw \Phrest\Http\Exception::MethodNotAllowed(
            new \Phrest\API\Error(
                0,
                'Method not allowed',
                new \Phrest\API\ErrorEntry(
                    0,
                    'method',
                    'Method "' . $method . '" not allowed',
                    implode(', ', $this->getAllowedMethods())
                )
            )
        );
    }

    public function get(RequestSwaggerData $data): \Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed('GET');
    }

    public function post(RequestSwaggerData $data): \Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed('POST');
    }

    public function put(RequestSwaggerData $data): \Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed('PUT');
    }

    public function patch(RequestSwaggerData $data): \Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed('PATCH');
    }

    public function delete(RequestSwaggerData $data): \Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed('DELETE');
    }
}