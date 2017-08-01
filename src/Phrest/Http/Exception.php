<?php
namespace Phrest\Http;

class Exception extends \Phrest\Exception
{
    private $error;

    public function __construct(int $statusCode, \Phrest\API\Error $error)
    {
        parent::__construct($error->message(), $statusCode);
        $this->error = $error;
    }

    public function error() : \Phrest\API\Error {
        return $this->error;
    }

    static public function BadRequest(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::BAD_REQUEST, $error);
    }

    static public function Unauthorized(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::UNAUTHORIZED, $error);
    }

    static public function PaymentRequired(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::PAYMENT_REQUIRED, $error);
    }

    static public function Forbidden(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::FORBIDDEN, $error);
    }

    static public function NotFound(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::NOT_FOUND, $error);
    }

    static public function MethodNotAllowed(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::METHOD_NOT_ALLOWED, $error);
    }

    static public function NotAcceptable(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::NOT_ACCEPTABLE, $error);
    }

    static public function ProxyAuthenticationRequired(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::PROXY_AUTHENTICATION_REQUIRED, $error);
    }

    static public function RequestTimeout(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::REQUEST_TIMEOUT, $error);
    }

    static public function Conflict(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::CONFLICT, $error);
    }

    static public function Gone(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::GONE, $error);
    }

    static public function LengthRequired(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::LENGTH_REQUIRED, $error);
    }

    static public function PreconditionFailed(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::PRECONDITION_FAILED, $error);
    }

    static public function RequestEntityTooLarge(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::REQUEST_ENTITY_TOO_LARGE, $error);
    }

    static public function RequestUriTooLong(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::REQUEST_URI_TOO_LONG, $error);
    }

    static public function UnsupportedMediaType(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::UNSUPPORTED_MEDIA_TYPE, $error);
    }

    static public function RequestedRangeNotSatisfiable(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::REQUESTED_RANGE_NOT_SATISFIABLE, $error);
    }

    static public function ExpectationFailed(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::EXPECTATION_FAILED, $error);
    }

    static public function InternalServerError(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::INTERNAL_SERVER_ERROR, $error);
    }

    static public function NotImplemented(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::NOT_IMPLEMENTED, $error);
    }

    static public function BadGateway(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::BAD_GATEWAY, $error);
    }

    static public function ServiceUnavailable(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::SERVICE_UNAVAILABLE, $error);
    }

    static public function GatewayTimeout(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::GATEWAY_TIMEOUT, $error);
    }

    static public function VersionNotSupported(\Phrest\API\Error $error) : Exception {
        return new Exception(\Phrest\Http\StatusCodes::VERSION_NOT_SUPPORTED, $error);
    }
}