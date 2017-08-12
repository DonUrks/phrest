<?php

namespace Phrest\API\Action;

class ErrorCodes implements \Interop\Http\ServerMiddleware\MiddlewareInterface
{
    /**
     * @var \Phrest\API\ErrorCodes
     */
    private $errorCodes;

    /**
     * @var \Zend\Cache\Storage\StorageInterface
     */
    private $cache;

    private const CACHE_ERROR_CODES = 'Phrest_API_Action_ErrorCodes';

    public function __construct(\Zend\Cache\Storage\StorageInterface $cache, \Phrest\API\ErrorCodes $errorCodes)
    {
        $this->errorCodes = $errorCodes;
        $this->cache = $cache;
    }

    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        if(!$this->cache->hasItem(self::CACHE_ERROR_CODES)) {
            $errorCodes = $this->errorCodes->getErrorCodes();
            $this->cache->setItem(self::CACHE_ERROR_CODES, serialize($errorCodes));
        } else {
            $errorCodes = unserialize($this->cache->getItem(self::CACHE_ERROR_CODES));
        }

        return new \Zend\Diactoros\Response\JsonResponse($errorCodes);
    }
}