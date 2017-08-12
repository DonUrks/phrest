<?php
namespace Phrest\Middleware;

class Error implements \Interop\Http\ServerMiddleware\MiddlewareInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (! (error_reporting() & $errno)) {
                return;
            }
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        try {
            $response = $delegate->process($request);
            if (! $response instanceof \Psr\Http\Message\ResponseInterface) {
                throw new \Phrest\Exception('Application did not return a response');
            }
        } catch (\Throwable $e) {
            $response = new \Zend\Diactoros\Response\JsonResponse(
                [
                    'code' => \Phrest\API\ErrorCodes::INTERNAL_SERVER_ERROR,
                    'message' => 'internal server error',
                    'http' => [
                        'code' => \Phrest\Http\StatusCodes::INTERNAL_SERVER_ERROR,
                        'message' => \Phrest\Http\StatusCodes::message(\Phrest\Http\StatusCodes::INTERNAL_SERVER_ERROR),
                    ],
                ],
                \Phrest\Http\StatusCodes::INTERNAL_SERVER_ERROR
            );

            $this->logger->error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString())
            ]);
        }

        restore_error_handler();

        return $response;
    }
}