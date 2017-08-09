<?php

namespace Phrest\Middleware;

class JsonRequestBody implements \Interop\Http\ServerMiddleware\MiddlewareInterface
{
    const RAW_BODY_ATTRIBUTE = self::class . '::RAW_BODY_ATTRIBUTE';
    const JSON_OBJECT_ATTRIBUTE = self::class . '::JSON_OBJECT_ATTRIBUTE';

    static private $jsonDecodeErrorMapping = [
        JSON_ERROR_DEPTH => \Phrest\API\ErrorCode::JSON_DECODE_ERROR_DEPTH,
        JSON_ERROR_STATE_MISMATCH => \Phrest\API\ErrorCode::JSON_DECODE_ERROR_STATE_MISMATCH,
        JSON_ERROR_CTRL_CHAR => \Phrest\API\ErrorCode::JSON_DECODE_ERROR_CTRL_CHAR,
        JSON_ERROR_SYNTAX => \Phrest\API\ErrorCode::JSON_DECODE_ERROR_SYNTAX,
        JSON_ERROR_UTF8 => \Phrest\API\ErrorCode::JSON_DECODE_ERROR_UTF8,
        JSON_ERROR_RECURSION => \Phrest\API\ErrorCode::JSON_DECODE_ERROR_RECURSION,
        JSON_ERROR_INF_OR_NAN => \Phrest\API\ErrorCode::JSON_DECODE_ERROR_INF_OR_NAN,
        JSON_ERROR_UNSUPPORTED_TYPE => \Phrest\API\ErrorCode::JSON_DECODE_ERROR_UNSUPPORTED_TYPE,
    ];

    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $match = $this->match($request->getHeaderLine('Content-Type'));
        if (!$match || in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $delegate->process($request);
        }

        $rawBody = (string)$request->getBody();
        $parsedBody = json_decode($rawBody, true);
        if (!empty($rawBody) && json_last_error() !== JSON_ERROR_NONE) {
            $errorEntryCode = self::$jsonDecodeErrorMapping[json_last_error()] ?? \Phrest\API\ErrorCode::UNKNOWN;
            throw \Phrest\Http\Exception::BadRequest(
                new \Phrest\API\Error(
                    \Phrest\API\ErrorCode::JSON_DECODE_ERROR,
                    'json decode error',
                    new \Phrest\API\ErrorEntry($errorEntryCode, '{body}', json_last_error_msg(), '')
                )
            );
        }

        return $delegate->process(
            $request
                ->withAttribute(self::RAW_BODY_ATTRIBUTE, $rawBody)
                ->withAttribute(self::JSON_OBJECT_ATTRIBUTE, json_decode($rawBody))
                ->withParsedBody($parsedBody)
        );
    }

    private function match($contentType)
    {
        $parts = explode(';', $contentType);
        $mime = array_shift($parts);
        return (bool)preg_match('#[/+]json$#', trim($mime));
    }
}