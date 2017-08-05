<?php

namespace Phrest\API;

class RequestBodyValidator
{
    /**
     * @var \JsonSchema\SchemaStorage
     */
    private $schemaStorage;

    /**
     * @var \JsonSchema\Validator
     */
    private $validator;

    static private $errorCodeMapping = [
        'additionalItems' => \Phrest\API\ErrorCode::JSON_SCHEMA_ADDITIONAL_ITEMS,
        'additionalProp' => \Phrest\API\ErrorCode::JSON_SCHEMA_ADDITIONAL_PROP,
        'allOf' => \Phrest\API\ErrorCode::JSON_SCHEMA_ALL_OF,
        'anyOf' => \Phrest\API\ErrorCode::JSON_SCHEMA_ANY_OF,
        'dependencies' => \Phrest\API\ErrorCode::JSON_SCHEMA_DEPENDENCIES,
        'disallow' => \Phrest\API\ErrorCode::JSON_SCHEMA_DISALLOW,
        'divisibleBy' => \Phrest\API\ErrorCode::JSON_SCHEMA_DIVISIBLE_BY,
        'enum' => \Phrest\API\ErrorCode::JSON_SCHEMA_ENUM,
        'exclusiveMaximum' => \Phrest\API\ErrorCode::JSON_SCHEMA_EXCLUSIVE_MAXIMUM,
        'exclusiveMinimum' => \Phrest\API\ErrorCode::JSON_SCHEMA_EXCLUSIVE_MINIMUM,
        'format' => \Phrest\API\ErrorCode::JSON_SCHEMA_FORMAT,
        'maximum' => \Phrest\API\ErrorCode::JSON_SCHEMA_MAXIMUM,
        'maxItems' => \Phrest\API\ErrorCode::JSON_SCHEMA_MAX_ITEMS,
        'maxLength' => \Phrest\API\ErrorCode::JSON_SCHEMA_MAX_LENGTH,
        'maxProperties' => \Phrest\API\ErrorCode::JSON_SCHEMA_MAX_PROPERTIES,
        'minimum' => \Phrest\API\ErrorCode::JSON_SCHEMA_MINIMUM,
        'minItems' => \Phrest\API\ErrorCode::JSON_SCHEMA_MIN_ITEMS,
        'minLength' => \Phrest\API\ErrorCode::JSON_SCHEMA_MIN_LENGTH,
        'minProperties' => \Phrest\API\ErrorCode::JSON_SCHEMA_MIN_PROPERTIES,
        'missingMaximum' => \Phrest\API\ErrorCode::JSON_SCHEMA_MISSING_MAXIMUM,
        'missingMinimum' => \Phrest\API\ErrorCode::JSON_SCHEMA_MISSING_MINIMUM,
        'multipleOf' => \Phrest\API\ErrorCode::JSON_SCHEMA_MULTIPLE_OF,
        'not' => \Phrest\API\ErrorCode::JSON_SCHEMA_NOT,
        'oneOf' => \Phrest\API\ErrorCode::JSON_SCHEMA_ONE_OF,
        'pattern' => \Phrest\API\ErrorCode::JSON_SCHEMA_PATTERN,
        'pregex' => \Phrest\API\ErrorCode::JSON_SCHEMA_PREGEX,
        'required' => \Phrest\API\ErrorCode::JSON_SCHEMA_REQUIRED,
        'requires' => \Phrest\API\ErrorCode::JSON_SCHEMA_REQUIRES,
        'schema' => \Phrest\API\ErrorCode::JSON_SCHEMA_SCHEMA,
        'type' => \Phrest\API\ErrorCode::JSON_SCHEMA_TYPE,
        'uniqueItems' => \Phrest\API\ErrorCode::JSON_SCHEMA_UNIQUE_ITEMS,
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

    public function __construct(\JsonSchema\SchemaStorage $schemaStorage, \JsonSchema\Validator $validator)
    {
        $this->schemaStorage = $schemaStorage;
        $this->validator = $validator;
    }

    public function validate(string $definition, \Psr\Http\Message\ServerRequestInterface $request): \stdClass
    {
        $rawBody = $request->getAttribute(\Phrest\Middleware\JsonRequestBody::RAW_BODY_ATTRIBUTE);
        $jsonObject = json_decode($rawBody);

        $this->validator->reset();
        $ref = $this->schemaStorage->resolveRef('file://swagger#/definitions/' . $definition);

        $this->validator->validate(
            $jsonObject,
            $ref,
            \JsonSchema\Constraints\Constraint::CHECK_MODE_NORMAL
            | \JsonSchema\Constraints\Constraint::CHECK_MODE_APPLY_DEFAULTS
            | \JsonSchema\Constraints\Constraint::CHECK_MODE_COERCE_TYPES
        );

        if (!$this->validator->isValid()) {
            $errors = [];
            foreach ($this->validator->getErrors() as $validatorError) {
                $more = array_intersect_key(
                    $validatorError,
                    array_flip(self::$moreFields)
                );

                $errors[] = new \Phrest\API\ErrorEntry(
                    self::$errorCodeMapping[$validatorError['constraint']] ?? \Phrest\API\ErrorCode::UNKNOWN,
                    $validatorError['pointer'],
                    $validatorError['message'],

                    /** @todo: find better format - custom formatter? */
                    json_encode($more)
                );
            }

            throw \Phrest\Http\Exception::BadRequest(
                new \Phrest\API\Error(
                    \Phrest\API\ErrorCode::JSON_SCHEMA_ERROR,
                    'request body json validation failed',
                    ...$errors
                )
            );
        }

        return $jsonObject;
    }
}