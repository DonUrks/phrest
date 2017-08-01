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

    public function __construct(\JsonSchema\SchemaStorage $schemaStorage, \JsonSchema\Validator $validator)
    {
        $this->schemaStorage = $schemaStorage;
        $this->validator = $validator;
    }

    public function validate(string $definition, \Psr\Http\Message\ServerRequestInterface $request)
    {
        $jsonObject = $request->getAttribute(\Phrest\Middleware\JsonRequestBody::JSON_OBJECT_ATTRIBUTE);

        $this->validator->reset();
        $ref = $this->schemaStorage->resolveRef('file://swagger#/definitions/'.$definition);

        $this->validator->validate($jsonObject, $ref);

        if (!$this->validator->isValid()) {
            //$out['errors'] = $this->validator->getErrors();

            throw \Phrest\Http\Exception::BadRequest(
                new \Phrest\API\Error(
                    0,
                    'request body json error'
                )
            );
        }
    }
}