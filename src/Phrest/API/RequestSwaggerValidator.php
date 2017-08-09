<?php

namespace Phrest\API;

/**
 * @todo validate consumes/produces = json
 */
class RequestSwaggerValidator
{
    static private $errorCodeMapping = [
        'additionalItems' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_ADDITIONAL_ITEMS,
        'additionalProp' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_ADDITIONAL_PROP,
        'allOf' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_ALL_OF,
        'anyOf' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_ANY_OF,
        'dependencies' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_DEPENDENCIES,
        'disallow' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_DISALLOW,
        'divisibleBy' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_DIVISIBLE_BY,
        'enum' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_ENUM,
        'exclusiveMaximum' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_EXCLUSIVE_MAXIMUM,
        'exclusiveMinimum' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_EXCLUSIVE_MINIMUM,
        'format' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_FORMAT,
        'maximum' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_MAXIMUM,
        'maxItems' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_MAX_ITEMS,
        'maxLength' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_MAX_LENGTH,
        'maxProperties' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_MAX_PROPERTIES,
        'minimum' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_MINIMUM,
        'minItems' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_MIN_ITEMS,
        'minLength' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_MIN_LENGTH,
        'minProperties' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_MIN_PROPERTIES,
        'missingMaximum' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_MISSING_MAXIMUM,
        'missingMinimum' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_MISSING_MINIMUM,
        'multipleOf' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_MULTIPLE_OF,
        'not' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_NOT,
        'oneOf' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_ONE_OF,
        'pattern' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_PATTERN,
        'pregex' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_PREGEX,
        'required' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_REQUIRED,
        'requires' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_REQUIRES,
        'schema' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_SCHEMA,
        'type' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_TYPE,
        'uniqueItems' => \Phrest\API\ErrorCode::REQUEST_VALIDATION_UNIQUE_ITEMS,
    ];

    static private $moreFields = [
        'minItems',
        'maxItems',
        'additionalItems',
        'enum',
        'format',
        'minimum',
        'maximum',
        'divisibleBy',
        'multipleOf',
        'pregex',
        'minProperties',
        'maxProperties',
        'maxLength',
        'minLength',
        'pattern',
    ];

    /**
     * @var \Phrest\Swagger
     */
    private $swagger;

    /**
     * @var \JsonSchema\Validator
     */
    private $validator;

    public function __construct(\Phrest\Swagger $swagger, \JsonSchema\Validator $validator)
    {
        $this->swagger = $swagger;
        $this->validator = $validator;
    }

    public function validate(\Psr\Http\Message\ServerRequestInterface $request, string $method, string $route): RequestSwaggerData
    {
        $parameters = $this->swagger->getParameters($route, $method);

        $errors = [];

        // validate query parameters
        $queryValues = [];
        foreach ($parameters->getQueryParameters() as $parameterName => $parameter) {
            $value = $request->getQueryParams()[$parameterName] ?? ($parameter['default'] ?? null);
            $errors = array_merge(
                $errors,
                $this->validateValueBySchema($value, $parameter, '{query}/' . $parameterName)
            );
            $queryValues[$parameterName] = $value;
        }

        // validate header parameters
        $headerValues = [];
        foreach ($parameters->getHeaderParameters() as $parameterName => $parameter) {
            $value = $request->getHeader($parameterName)[0] ?? ($parameter['default'] ?? null);
            $errors = array_merge(
                $errors,
                $this->validateValueBySchema($value, $parameter, '{header}/' . $parameterName)
            );
            $headerValues[$parameterName] = $value;
        }

        // validate path parameters
        $pathValues = [];
        foreach ($parameters->getPathParameters() as $parameterName => $parameter) {
            /* @todo default: does this make sense? */
            $value = $request->getAttribute($parameterName, $parameter['default'] ?? null);
            $errors = array_merge(
                $errors,
                $this->validateValueBySchema($value, $parameter, '{path}/' . $parameterName)
            );
            $pathValues[$parameterName] = $value;
        }

        // validate body
        $bodyValue = null;
        $bodyParameter = $parameters->getBodyParameter();
        if (count($bodyParameter)) {
            $value = $request->getAttribute(\Phrest\Middleware\JsonRequestBody::JSON_OBJECT_ATTRIBUTE);
            $errors = array_merge(
                $errors,
                $this->validateValueBySchema($value, $bodyParameter['schema'], '{body}')
            );
            $bodyValue = $value;
        }

        if (count($errors)) {
            throw \Phrest\Http\Exception::BadRequest(
                new \Phrest\API\Error(
                    \Phrest\API\ErrorCode::REQUEST_PARAMETER_VALIDATION,
                    'request parameter validation failed',
                    ...$errors
                )
            );
        }

        return new RequestSwaggerData($bodyValue, $queryValues, $pathValues, $headerValues);
    }

    private function validateValueBySchema(&$value, $schema, $fieldName)
    {
        $this->validator->reset();

        $this->validator->validate(
            $value,
            $schema,
            \JsonSchema\Constraints\Constraint::CHECK_MODE_NORMAL
            | \JsonSchema\Constraints\Constraint::CHECK_MODE_APPLY_DEFAULTS
            | \JsonSchema\Constraints\Constraint::CHECK_MODE_COERCE_TYPES
        );

        $errors = [];
        if (!$this->validator->isValid()) {
            foreach ($this->validator->getErrors() as $validatorError) {
                $more = array_intersect_key(
                    $validatorError,
                    array_flip(self::$moreFields)
                );

                $errors[] = new \Phrest\API\ErrorEntry(
                    self::$errorCodeMapping[$validatorError['constraint']] ?? \Phrest\API\ErrorCode::UNKNOWN,
                    $fieldName . $validatorError['pointer'],
                    $validatorError['message'],

                    /** @todo: find better format - custom formatter? */
                    json_encode($more)
                );
            }
        }

        return $errors;
    }
}