<?php

namespace Phrest;

/**
 * @todo parameter location formData
 * @todo recursive to array conversion
 * @todo cache parameter preparation - all at once!?
 * @todo Paths can be a ref: https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#pathsObject
 */
class Swagger
{
    const SCHEMA_ID = 'file://swagger';

    private const CACHE_SWAGGER = 'Phrest_Swagger';

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
    }

    public function getSchemaStorage(): \JsonSchema\SchemaStorage
    {
        return $this->schemaStorage;
    }

    public function getParameters(string $route, string $method): Swagger\Parameters
    {
        // @todo: find a collision free method for cache key generation
        $cacheKey = self::CACHE_SWAGGER . '_' . md5($method . '_' . $route);

        if ($this->cache->hasItem($cacheKey)) {
            return unserialize($this->cache->getItem($cacheKey));
        }

        $paths = (array)$this->schemaStorage->resolveRef(self::SCHEMA_ID . '#/paths');
        if (!array_key_exists($route, $paths)) {
            throw new \Phrest\Exception('route "' . $route . '" not found in schema');
        }

        $path = (array)$paths[$route];
        if (!array_key_exists($method, $path)) {
            throw new \Phrest\Exception('method "' . $method . '" for route "' . $route . '" not found in schema');
        }

        $method = (array)$path[$method];

        // parameters can be defined for all operations - parameter in operations will override them
        $parameters = (array)($path['parameters'] ?? []);
        $parameters = array_merge($parameters, (array)($method['parameters'] ?? []));

        // prepare parameters
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
                    throw new \Phrest\Exception('swagger parameter location "formData" not yet implemented');
                    break;
            }
        }

        $swaggerParameters = new Swagger\Parameters($bodyParameter, $queryParameters, $headerParameters, $pathParameters);

        $this->cache->setItem($cacheKey, serialize($swaggerParameters));

        return $swaggerParameters;
    }

    public function __toString(): string
    {
        return $this->swagger;
    }
}