<?php
namespace Phrest\Middleware;

class NotFound implements \Interop\Http\ServerMiddleware\MiddlewareInterface
{
    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        throw \Phrest\Http\Exception::NotFound(
            new \Phrest\API\Error(\Phrest\API\ErrorCode::PATH_NOT_FOUND, 'path not found')
        );
    }
}