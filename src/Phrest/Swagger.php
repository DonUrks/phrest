<?php

namespace Phrest;

class Swagger
{
    const SCHEMA_ID = 'file://swagger';

    private const CACHE_SWAGGER = 'Phrest_Swagger';

    private const CACHE_SWAGGER_OPERATION_PARAMETERS = 'Phrest_Swagger_Operation_Parameters';

    /**
     * @var \Zend\Cache\Storage\StorageInterface
     */
    private $cache;

    /**
     * @var \JsonSchema\SchemaStorage
     */
    private $schemaStorage;

    /**
     * @var string
     */
    private $swagger;

    /**
     * @var array
     */
    private $parametersByOperationId;

    public function __construct(\Zend\Cache\Storage\StorageInterface $cache, string $swaggerScanDirectory)
    {
        $this->cache = $cache;

        if (!$this->cache->hasItem(self::CACHE_SWAGGER)) {
            $this->swagger = \Swagger\scan($swaggerScanDirectory);
            $this->cache->setItem(self::CACHE_SWAGGER, $this->swagger);
        } else {
            $this->swagger = $this->cache->getItem(self::CACHE_SWAGGER);
        }

        $this->schemaStorage = new \JsonSchema\SchemaStorage();
        $this->schemaStorage->addSchema(Swagger::SCHEMA_ID, json_decode($this->swagger));

        if ($this->cache->hasItem(self::CACHE_SWAGGER_OPERATION_PARAMETERS)) {
            $this->parametersByOperationId = unserialize($this->cache->getItem(self::CACHE_SWAGGER_OPERATION_PARAMETERS));
        } else {
            $this->parametersByOperationId = $this->extractParameters();
            $this->cache->setItem(self::CACHE_SWAGGER_OPERATION_PARAMETERS, serialize($this->parametersByOperationId));
        }
    }

    public function getSchemaStorage(): \JsonSchema\SchemaStorage
    {
        return $this->schemaStorage;
    }

    private function extractParameters(): array
    {
        // Swagger Paths[]
        $paths = (array)$this->schemaStorage->resolveRef(self::SCHEMA_ID . '#/paths');

        $rawParametersByOperationId = [];
        foreach ($paths as $path => $pathItem) {
            // $path => '/some-path'
            // $pathItem => Swagger Path Item Object

            // Resolve Swagger Path Item Object $ref
            // "If there are conflicts between the referenced definition and this Path Item's definition, the behavior is undefined."
            // So we just allow either direct definition or using of $ref. But never both. $ref will overwrite all direct definitions.
            if (property_exists($pathItem, '$ref')) {
                $pathItemArray = (array)$pathItem;
                $pathItem = $this->schemaStorage->resolveRef($pathItemArray['$ref']);
            }

            // Handle @SWG\Parameter on @SWG\Path
            $pathItemParameters = [];
            if (property_exists($pathItem, 'parameters')) {
                $pathItemParameters = (array)($pathItem->parameters ?? []);

                // Resolve Swagger Path Item Parameters $ref
                if (array_key_exists('$ref', $pathItemParameters)) {
                    $pathItemParameters = (array)$this->schemaStorage->resolveRef($pathItemParameters['$ref']);
                }
            }

            $operations = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch'];
            foreach ($operations as $operationName) {
                if (!property_exists($pathItem, $operationName)) {
                    continue;
                }
                $operation = $pathItem->$operationName;

                if (!property_exists($operation, 'operationId')) {
                    continue;
                }

                $operationId = $operation->operationId;

                if (array_key_exists($operationId, $rawParametersByOperationId)) {
                    throw new \Phrest\Exception('Swagger operationId duplicate found (' . implode('::', [$path, $operationName, $operationId]) . ').');
                }

                $parameters = [];
                if (property_exists($operation, 'parameters')) {
                    $parameters = (array)$operation->parameters;
                }

                // parameters can be defined for all operations - parameter in operations will override them
                $rawParametersByOperationId[$operationId] = array_merge($pathItemParameters, $parameters);
            }
        }

        $parametersByOperationId = [];
        foreach ($rawParametersByOperationId as $operationId => $parameters) {
            $parametersByOperationId[$operationId] = $this->extractParametersFromRawParameters($parameters);
        }

        return $parametersByOperationId;
    }

    private function extractParametersFromRawParameters(array $parameters): Swagger\Parameters
    {
        $bodyParameter = [];
        $queryParameters = [];
        $headerParameters = [];
        $pathParameters = [];
        foreach ($parameters as $parameter) {
            $parameter = (array)$parameter;

            // resolve refs
            if (array_key_exists('$ref', $parameter)) {
                $parameter = (array)$this->schemaStorage->resolveRef($parameter['$ref']);
            }

            switch ($parameter['in']) {
                case 'query':
                    $queryParameters[$parameter['name']] = $parameter;
                    break;
                case 'body':
                    $bodyParameter = $parameter;
                    $ref = (array)$bodyParameter['schema'];
                    $bodyParameter['schema'] = $this->schemaStorage->resolveRef($ref['$ref']);
                    break;
                case 'header':
                    $headerParameters[$parameter['name']] = $parameter;
                    break;
                case 'path':
                    $pathParameters[$parameter['name']] = $parameter;
                    break;
                case 'formData':
                    // @todo implement formData validation
                    // throw new \Phrest\Exception('swagger parameter location "formData" not yet implemented');
                    break;
            }
        }
        return new Swagger\Parameters($bodyParameter, $queryParameters, $headerParameters, $pathParameters);
    }

    public function getParameters(string $operationId): Swagger\Parameters
    {
        if (!array_key_exists($operationId, $this->parametersByOperationId)) {
            throw new \Phrest\Exception('OperationId "' . $operationId . '" not found in swagger.');
        }
        return $this->parametersByOperationId[$operationId];
    }

    public function __toString(): string
    {
        return $this->swagger;
    }
}
