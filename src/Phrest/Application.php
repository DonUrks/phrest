<?php

namespace Phrest;

use Zend\ConfigAggregator\PhpFileProvider;
use Zend\ConfigAggregator\ArrayProvider;
use Zend\ConfigAggregator\ConfigAggregator;

use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

class Application
{
    const CONFIG_SWAGGER_SCAN_DIRECTORY = 'phrest_config_swagger_scan_directory';
    const CONFIG_ENABLE_CACHE = 'phrest_config_enable_cache';
    const CONFIG_CACHE_DIRECTORY = 'phrest_config_cache_directory';
    const CONFIG_MONOLOG_HANDLER = 'phrest_config_monolog_handler';
    const CONFIG_MONOLOG_PROCESSOR = 'phrest_config_monolog_processor';
    const CONFIG_PRE_ROUTING_MIDDLEWARE = 'phrest_config_pre_routing_middleware';
    const CONFIG_PRE_DISPATCHING_MIDDLEWARE = 'phrest_config_pre_dispatching_middleware';
    const CONFIG_ROUTES = 'phrest_config_routes';
    const CONFIG_DEPENDENCIES = 'phrest_config_dependencies';
    const CONFIG_ERROR_CODES = 'phrest_config_error_codes';

    const ACTION_SWAGGER = 'phrest_action_swagger';
    const ACTION_ERROR_CODES = 'phrest_action_error_codes';

    const SERVICE_LOGGER = 'phrest_service_logger';
    const SERVICE_SWAGGER = 'phrest_service_swagger';
    const SERVICE_HATEOAS_RESPONSE_GENERATOR = 'phrest_service_hateoas_response_generator';
    const SERVICE_REQUEST_SWAGGER_VALIDATOR = 'phrest_service_request_swagger_validator';

    static public function run(string $applicationName = 'phrest-application', string $configDirectoryPattern = "config/{{,*.}global,{,*.}local}.php")
    {
        $logger = new \Monolog\Logger($applicationName, [new \Monolog\Handler\StreamHandler('php://stdout')]);
        \Monolog\ErrorHandler::register($logger);

        $logger->debug('application init started', ['configDirectoryPattern' => $configDirectoryPattern]);

        $userConfigAggregator = new ConfigAggregator([
            new PhpFileProvider($configDirectoryPattern),
        ]);
        $userConfig = $userConfigAggregator->getMergedConfig();

        $swaggerScanDirectory = (string)($userConfig[\Phrest\Application::CONFIG_SWAGGER_SCAN_DIRECTORY] ?? null);

        $enableCache = (boolean)($userConfig[\Phrest\Application::CONFIG_ENABLE_CACHE] ?? false);
        $cacheDirectory = $userConfig[\Phrest\Application::CONFIG_CACHE_DIRECTORY] ?? null;

        $monologHandler = $userConfig[\Phrest\Application::CONFIG_MONOLOG_HANDLER] ?? [];
        $monologProcessor = $userConfig[\Phrest\Application::CONFIG_MONOLOG_PROCESSOR] ?? [];

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
                'dependencies' => $userConfig[\Phrest\Application::CONFIG_DEPENDENCIES] ?? []
            ]),
            new ArrayProvider([
                ConfigAggregator::ENABLE_CACHE => $enableCache,
                'zend-expressive' => [
                    'programmatic_pipeline' => true,
                ],
                'dependencies' => [
                    'factories' => [
                        \Zend\Expressive\Helper\UrlHelper::class => \Zend\Expressive\Helper\UrlHelperFactory::class,

                        \Phrest\Application::SERVICE_LOGGER => function (\Interop\Container\ContainerInterface $container) use ($logger, $monologHandler, $monologProcessor) {
                            foreach ($monologHandler as $handler) {
                                $logger->pushHandler($container->get($handler));
                            }
                            foreach ($monologProcessor as $processor) {
                                $logger->pushProcessor($container->get($processor));
                            }
                            return $logger;
                        },

                        \Phrest\Application::SERVICE_SWAGGER => function () use ($cache, $swaggerScanDirectory) {
                            return new \Phrest\Swagger($cache, $swaggerScanDirectory);
                        },

                        \Phrest\Application::ACTION_SWAGGER => function (\Interop\Container\ContainerInterface $container) {
                            return new \Phrest\API\Action\Swagger($container->get(\Phrest\Application::SERVICE_SWAGGER));
                        },

                        \Phrest\Application::ACTION_ERROR_CODES => function (\Interop\Container\ContainerInterface $container) use ($cache) {
                            if ($container->has(\Phrest\Application::CONFIG_ERROR_CODES)) {
                                $errorCodes = $container->get(\Phrest\Application::CONFIG_ERROR_CODES);
                            } else {
                                $errorCodes = new API\ErrorCodes();
                            }
                            return new \Phrest\API\Action\ErrorCodes($cache, $errorCodes);
                        },

                        \Phrest\Application::SERVICE_HATEOAS_RESPONSE_GENERATOR => function () use ($hateoas) {
                            return new \Phrest\API\HateoasResponseGenerator($hateoas);
                        },

                        \Phrest\Application::SERVICE_REQUEST_SWAGGER_VALIDATOR => function (\Interop\Container\ContainerInterface $container) {
                            /** @var \Phrest\Swagger $swagger */
                            $swagger = $container->get(\Phrest\Application::SERVICE_SWAGGER);
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
                    'initializers' => [
                        \Psr\Log\LoggerAwareInterface::class => function (\Interop\Container\ContainerInterface $container, $service) {
                            if ($service instanceof \Psr\Log\LoggerAwareInterface) {
                                $service->setLogger(
                                    $container->get(\Phrest\Application::SERVICE_LOGGER)
                                );
                            }
                        },
                        API\RequestSwaggerValidatorAwareInterface::class => function (\Interop\Container\ContainerInterface $container, $service) {
                            if ($service instanceof API\RequestSwaggerValidatorAwareInterface) {
                                $service->setRequestSwaggerValidator(
                                    $container->get(\Phrest\Application::SERVICE_REQUEST_SWAGGER_VALIDATOR)
                                );
                            }
                        },
                        API\HateoasResponseGeneratorAwareInterface::class => function (\Interop\Container\ContainerInterface $container, $service) {
                            if ($service instanceof API\HateoasResponseGeneratorAwareInterface) {
                                $service->setHateoasResponseGenerator(
                                    $container->get(\Phrest\Application::SERVICE_HATEOAS_RESPONSE_GENERATOR)
                                );
                            }
                        },
                    ]
                ]
            ]),
        ]);
        $internalConfig = $internalConfigAggregator->getMergedConfig();

        $container = new ServiceManager();
        (new Config($internalConfig['dependencies'] ?? []))->configureServiceManager($container);
        $container->setService('config', $userConfig);

        // Register logging handler / processors - can only happen after loading user dependencies
        $container->get(\Phrest\Application::SERVICE_LOGGER);

        $app = new \Zend\Expressive\Application($container->get(\Zend\Expressive\Router\RouterInterface::class), $container);

        $routes = $userConfig[\Phrest\Application::CONFIG_ROUTES] ?? [];
        foreach ($routes as $name => $route) {
            $app->route($route['path'], $route['action'], ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $name);
        }

        $app->pipe(new \Phrest\Middleware\Error($logger));
        $app->pipe(new \Phrest\Middleware\HttpException($logger));
        $app->pipe(new \Zend\Expressive\Helper\ServerUrlMiddleware($container->get(\Zend\Expressive\Helper\ServerUrlHelper::class)));
        $app->pipe(new \Phrest\Middleware\JsonRequestBody());

        $app->pipe($userConfig[\Phrest\Application::CONFIG_PRE_ROUTING_MIDDLEWARE] ?? []);
        $app->pipeRoutingMiddleware();

        $app->pipe(new \Zend\Expressive\Helper\UrlHelperMiddleware($container->get(\Zend\Expressive\Helper\UrlHelper::class)));

        $app->pipe($userConfig[\Phrest\Application::CONFIG_PRE_DISPATCHING_MIDDLEWARE] ?? []);
        $app->pipeDispatchMiddleware();

        $app->pipe(new \Phrest\Middleware\NotFound());

        $logger->debug('application init completed', ['userConfig' => $userConfig]);

        $app->run();
    }

    static public function createRoute(string $path, string $action): array
    {
        return [
            'path' => $path,
            'action' => $action
        ];
    }
}