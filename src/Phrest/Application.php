<?php

namespace Phrest;

use Zend\ConfigAggregator\PhpFileProvider;
use Zend\ConfigAggregator\ArrayProvider;
use Zend\ConfigAggregator\ConfigAggregator;

use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

class Application
{
    static public function run(string $applicationName = 'phrest-application', string $configDirectoryPattern = "config/{{,*.}global,{,*.}local}.php")
    {
        $logger = new \Monolog\Logger($applicationName, [new \Monolog\Handler\StreamHandler('php://stdout')]);
        \Monolog\ErrorHandler::register($logger);

        $logger->debug('application init started', ['configDirectoryPattern' => $configDirectoryPattern]);

        $userConfigAggregator = new ConfigAggregator([
            new PhpFileProvider($configDirectoryPattern),
        ]);
        $userConfig = $userConfigAggregator->getMergedConfig();

        $swaggerScanDirectory = (string)($userConfig[\Phrest\Config::SWAGGER_SCAN_DIRECTORY] ?? null);

        $enableCache = (boolean)($userConfig[\Phrest\Config::ENABLE_CACHE] ?? false);
        $cacheDirectory = $userConfig[\Phrest\Config::CACHE_DIRECTORY] ?? null;

        $monologHandler = $userConfig[\Phrest\Config::MONOLOG_HANDLER] ?? [];
        $monologProcessor = $userConfig[\Phrest\Config::MONOLOG_PROCESSOR] ?? [];

        /** @var \Zend\Cache\Storage\StorageInterface $cache */
        $cache = new \Zend\Cache\Storage\Adapter\BlackHole();
        if ($enableCache) {
            $cache = new \Zend\Cache\Storage\Adapter\Filesystem();
            $cache->setOptions([
                'cache_dir' => $cacheDirectory,
                'ttl' => 0
            ]);
        }

        // HATEOAS
        /* @todo registerLoader is deprecated! */
        \Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');
        $hateoasBuilder = \Hateoas\HateoasBuilder::create();
        if ($enableCache) {
            $hateoasBuilder->setCacheDir($cacheDirectory);
        }
        $hateoas = $hateoasBuilder->build();

        $internalConfigAggregator = new ConfigAggregator([
            new ArrayProvider([
                'dependencies' => $userConfig[\Phrest\Config::DEPENDENCIES] ?? []
            ]),
            new ArrayProvider([
                ConfigAggregator::ENABLE_CACHE => $enableCache,
                'zend-expressive' => [
                    'programmatic_pipeline' => true,
                ],
                'dependencies' => [
                    'factories' => [
                        \Zend\Expressive\Helper\UrlHelper::class => \Zend\Expressive\Helper\UrlHelperFactory::class,

                        \Phrest\Config::LOGGER => function (\Interop\Container\ContainerInterface $container) use ($logger, $monologHandler, $monologProcessor) {
                            foreach ($monologHandler as $handler) {
                                $logger->pushHandler($container->get($handler));
                            }
                            foreach ($monologProcessor as $processor) {
                                $logger->pushProcessor($container->get($processor));
                            }
                            return $logger;
                        },

                        \Phrest\Config::SWAGGER => function () use ($cache, $swaggerScanDirectory) {
                            return new \Phrest\Swagger($cache, $swaggerScanDirectory);
                        },

                        \Phrest\Config::ACTION_SWAGGER => function (\Interop\Container\ContainerInterface $container) {
                            return new \Phrest\API\Action\Swagger($container->get(\Phrest\Config::SWAGGER));
                        },

                        \Phrest\Config::ACTION_ERROR_CODES => function (\Interop\Container\ContainerInterface $container) use($cache) {
                            if($container->has(\Phrest\Config::ERROR_CODES)) {
                                $errorCodes = $container->get(\Phrest\Config::ERROR_CODES);
                            } else {
                                $errorCodes = new API\ErrorCodes();
                            }
                            return new \Phrest\API\Action\ErrorCodes($cache, $errorCodes);
                        },

                        \Phrest\Config::HATEOAS_RESPONSE_GENERATOR => function () use ($hateoas) {
                            return new \Phrest\API\HateoasResponseGenerator($hateoas);
                        },

                        \Phrest\Config::REQUEST_SWAGGER_VALIDATOR => function (\Interop\Container\ContainerInterface $container) {
                            /** @var \Phrest\Swagger $swagger */
                            $swagger = $container->get(\Phrest\Config::SWAGGER);
                            $jsonValidator = new \JsonSchema\Validator(
                                new \JsonSchema\Constraints\Factory($swagger->getSchemaStorage())
                            );
                            return new \Phrest\API\RequestSwaggerValidator($swagger, $jsonValidator);
                        },
                    ],
                    'invokables' => [
                        \Zend\Expressive\Router\RouterInterface::class => \Zend\Expressive\Router\FastRouteRouter::class,
                        \Zend\Expressive\Helper\ServerUrlHelper::class => \Zend\Expressive\Helper\ServerUrlHelper::class
                    ],
                ]
            ]),
        ]);
        $internalConfig = $internalConfigAggregator->getMergedConfig();

        $container = new ServiceManager();
        (new Config($internalConfig['dependencies'] ?? []))->configureServiceManager($container);
        $container->setService('config', $userConfig);

        // Register logging handler / processors - can only happen after loading user dependencies
        $container->get(\Phrest\Config::LOGGER);

        $app = new \Zend\Expressive\Application($container->get(\Zend\Expressive\Router\RouterInterface::class), $container);

        $routes = $userConfig[\Phrest\Config::ROUTES] ?? [];
        foreach ($routes as $name => $route) {
            $app->route($route['path'], $route['action'], ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $name);
        }

        $app->pipe(new \Phrest\Middleware\Error($logger));
        $app->pipe(new \Phrest\Middleware\HttpException($logger));
        $app->pipe(new \Zend\Expressive\Helper\ServerUrlMiddleware($container->get(\Zend\Expressive\Helper\ServerUrlHelper::class)));
        $app->pipe(new \Phrest\Middleware\JsonRequestBody());

        $app->pipe($userConfig[\Phrest\Config::PRE_ROUTING_MIDDLEWARE] ?? []);
        $app->pipeRoutingMiddleware();

        $app->pipe(new \Zend\Expressive\Helper\UrlHelperMiddleware($container->get(\Zend\Expressive\Helper\UrlHelper::class)));

        $app->pipe($userConfig[\Phrest\Config::PRE_DISPATCHING_MIDDLEWARE] ?? []);
        $app->pipeDispatchMiddleware();

        $app->pipe($userConfig[\Phrest\Config::POST_DISPATCHING_MIDDLEWARE] ?? []);
        $app->pipe(new \Phrest\Middleware\NotFound());

        $logger->debug('application init completed', ['userConfig' => $userConfig]);

        $app->run();
    }
}