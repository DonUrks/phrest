<?php

namespace Phrest\API;


trait RESTActionTrait
{
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
        $method = strtolower($request->getMethod());

        $this->logger->debug(__METHOD__ . ' called', ['method' => $method]);

        if (!array_key_exists($method, self::$methods)) {
            $this->throwMethodNotAllowed($method);
        }

        if ($method === 'options') {
            return $this->options();
        }

        return $this->onRESTRequest($request, $method);
    }

    private function options(): \Psr\Http\Message\ResponseInterface
    {
        return new \Zend\Diactoros\Response\EmptyResponse(
            \Phrest\Http\StatusCodes::NO_CONTENT,
            [
                'Allow' => implode(', ', $this->getAllowedMethods())
            ]
        );
    }

    protected function onRESTRequest(\Psr\Http\Message\ServerRequestInterface $request, string $method): \Psr\Http\Message\ResponseInterface
    {
        $this->throwMethodNotAllowed($method);
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
}