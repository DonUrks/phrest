<?php
namespace Phrest\Middleware;

class HttpException implements \Interop\Http\ServerMiddleware\MiddlewareInterface
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
        try {
            $response = $delegate->process($request);
        } catch(\Phrest\Http\Exception $e) {
            $error = $e->error();

            $errorEntries = [];
            foreach($error->errors() as $errorEntry) {
                /** @var \Phrest\API\ErrorEntry $errorEntry */
                $errorEntries[] = [
                    'code' => $errorEntry->code(),
                    'message' => $errorEntry->message(),
                    'field' => $errorEntry->field(),
                    'constraint' => $errorEntry->constraint()
                ];
            }

            $response = new \Zend\Diactoros\Response\JsonResponse(
                [
                    'code' => $error->code(),
                    'message' => $error->message(),
                    'http' => [
                        'code' => $e->getCode(),
                        'message' => \Phrest\Http\StatusCodes::message($e->getCode()),
                    ],
                    'errors' => $errorEntries
                ],
                $e->getCode()
            );

            $this->logger->info('Exception: '.$e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString())
            ]);
        }
        return $response;
    }
}