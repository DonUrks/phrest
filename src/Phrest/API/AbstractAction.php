<?php

namespace Phrest\API;

abstract class AbstractAction implements \Interop\Http\ServerMiddleware\MiddlewareInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

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

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function logger(): \Psr\Log\LoggerInterface
    {
        return $this->logger;
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

    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $method = $request->getMethod();

        $this->logger()->debug(__METHOD__ . ' called', ['method' => $method]);


        if (!in_array($method, self::$methods)) {
            $this->throwMethodNotAllowed($method);
        }

        $method = strtolower($method);
        return call_user_func_array([$this, $method], [$request]);
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