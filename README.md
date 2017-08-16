# phrest

A php framework for your RESTful API with JSON and Swagger support. Phrest will automatically scan your code for swagger or HATEOAS annotations. If desired phrest will also use your scanned swagger annotations for request data validation.

## Features
- Swagger 2.0 definitions in annotations ([zircote/swagger-php](https://github.com/zircote/swagger-php))
- HATEOAS response definitions in annotations ([willdurand/Hateoas](https://github.com/willdurand/Hateoas))
- Request data validation against swagger data ([justinrainbow/json-schema](https://github.com/justinrainbow/json-schema))
- PSR-3 Logging ([Seldaek/monolog](https://github.com/Seldaek/monolog))
- Expandable with PSR-15 middleware
- PSR-7 messages
- Error codes for API consumers
- Unified exception handling for HTTP status codes

## Requirements
- PHP 7.1

## Installation (with [Composer](https://getcomposer.org))
### Command line
```sh
composer require donurks/phrest
```
### public/index.php
```php
<?php
chdir(dirname(__DIR__));
require_once "vendor/autoload.php";

\Phrest\Application::run('phrest-example');
```

## Quickstart (with [donurks/phrest-skeleton](https://github.com/DonUrks/phrest-skeleton))

```sh
composer create-project donurks/phrest-skeleton
```

## \Phrest\Application::run parameters
Name | Type | Default | Description
---|:---:|:---:|---
applicationName | string | phrest-application | The name of your application. Used for Logging.
configDirectoryPattern | string | config/{{,*.}global,{,*.}local}.php | The glob pattern used for loading and merging your config files.
request | \Psr\Http\Message\ServerRequestInterface | ServerRequestFactory::fromGlobals() | If you want to provide your own request object instead of using the global variables. Useful for unit testing.

## Configuration
By default phrest will look at your config/ directory and will load and merge all config files in the following order:
- global.php
- *.global.php
- local.php
- *.local.php

```php
<?php
// Config files should return arrays
return [
    'my-config-entry' => 'my-config-value'
];
```

You can use your own config file load pattern (glob) by providing a second parameter to ```\Phrest\Application::run```

```php
<?php
\Phrest\Application::run('phrest-example', 'my_own_config_dir/just_one_config_file.php');
``` 

### Config
For phrest configuration there are predefined class constants on ```\Phrest\Application``` which you can use as config entries in your config array.

```php
<?php
return [
    \Phrest\Application::CONFIG_ENABLE_CACHE  => true
];
```

\Phrest\Application constant | Type | Description
---|:---:|---
CONFIG_ENABLE_CACHE | boolean | If true, phrest will cache swagger, HATEOAS and configurations. If true ```CONFIG_CACHE_DIRECTORY``` is **required**! 
CONFIG_CACHE_DIRECTORY | string | The directory where phrest will cache. Make sure this directory is phrest exclusive to avoid conflicts (```cache/phrest/``` is a good choice).
CONFIG_SWAGGER_SCAN_DIRECTORY | string | Tells phrest where to look for your swagger annotations. Usually this is your ```src/``` directory.
CONFIG_DEPENDENCIES | array | Phrest uses the [zend-servicemanager](https://zendframework.github.io/zend-servicemanager/). Place your zend-servicemanager config here.
CONFIG_ROUTES | array['path' => string, 'action' => string] | Bring path and action together. Each action must be a string refer to a service defined in ```CONFIG_DEPENDENCIES```. Must implement ```\Interop\Http\ServerMiddleware\MiddlewareInterface``` - or just use phrest [Abstract actions](#abstract-actions). See also [Routing](#routing).
CONFIG_MONOLOG_HANDLER | string[] | You can register one or more Monolog handlers. Each string must refer to a service defined in ```CONFIG_DEPENDENCIES```.
CONFIG_MONOLOG_PROCESSOR | string[] | You can register one or more Monolog processors. Each string must refer to a service defined in ```CONFIG_DEPENDENCIES```.
CONFIG_ERROR_CODES | string | Tells phrest to use your error codes class. String must refer to a service defined in ```CONFIG_DEPENDENCIES```. Must extends ```\Phrest\API\ErrorCodes```. See [Using your own error codes](#using-your-own-error-codes).
CONFIG_PRE_ROUTING_MIDDLEWARE | string[] | Register your own middleware called before routing. Each string must refer to a service defined in ```CONFIG_DEPENDENCIES```. Must implements ```\Interop\Http\ServerMiddleware\MiddlewareInterface```.
CONFIG_PRE_DISPATCHING_MIDDLEWARE | string[] | Register your own middleware called before dispatching the action. Each string must refer to a service defined in ```CONFIG_DEPENDENCIES```. Must implements ```\Interop\Http\ServerMiddleware\MiddlewareInterface```.

### User config
Your whole configuration is accessible in the container with the ```\Phrest\Application::USER_CONFIG``` constant.

```php
<?php
return [
    'my-own-config' => 'some-value',

    \Phrest\Application::CONFIG_DEPENDENCIES => [
        'factories' => [
            \Application\Action\SomeAction::class => function (\Interop\Container\ContainerInterface $container) {
                $userConfig = $container->get(\Phrest\Application::USER_CONFIG);
                $myOwnConfigValue = $userConfig['my-own-config'];
                
                // ...
            },
        ]
    ],
];
``` 

### Services
Phrest provides several services for you. You can access them in your zend-servicemanager factory container.

```php
<?php
return [
    \Phrest\Application::CONFIG_DEPENDENCIES => [
        'factories' => [
            \Application\Action\SomeAction::class => function (\Interop\Container\ContainerInterface $container) {
                return new \Application\Action\SomeAction(
                    $container->get(\Phrest\Application::SERVICE_LOGGER)
                );
            },
        ]
    ],
];
``` 

\Phrest\Application constant | Interface | Description
---|:---:|---
SERVICE_LOGGER | \Psr\Log\LoggerInterface | Writes log entries to all registered ```CONFIG_MONOLOG_HANDLER```
SERVICE_ROUTER | \Zend\Expressive\Router\RouterInterface | The router used to determine the action.
SERVICE_SWAGGER | \Phrest\Swagger | The phrest swagger abstraction.
SERVICE_HATEOAS | \Hateoas\Hateoas | The HATEOAS serializer / deserializer.
SERVICE_HATEOAS_RESPONSE_GENERATOR | \Phrest\API\HateoasResponseGenerator | Can be used to generate json response with the help of willdurand/Hateoas. See [HATEOAS response generator](#hateoas-response-generator)
SERVICE_REQUEST_SWAGGER_VALIDATOR | \Phrest\API\RequestSwaggerValidator | Can be used to validate request data against swagger schema. See [Request swagger validator](#request-swagger-validator)   

### Actions
Phrest provides several actions for you. You can use them by simple bound them to paths.

```php
<?php
return [
    \Phrest\Application::CONFIG_ROUTES => [
        // call http://your-host/swagger to see your swagger file
        'swagger' => \Phrest\Application::createRoute(
            '/swagger', 
            \Phrest\Application::ACTION_SWAGGER
        ),
    ],
];
```

\Phrest\Application constant | Description
---|---
ACTION_SWAGGER | Provides the swagger file in json format.
ACTION_ERROR_CODES | Provides all possible error codes in json format. See [Error Codes](#error-codes).

## Routing

## Abstract actions

## Logging

## HATEOAS response generator

## Request swagger validator

## Exceptions

## Error codes
You can use the phrest error codes action to publish your error codes for your API consumers.
```php
<?php
// your configuration file
return [
    \Phrest\Application::CONFIG_ROUTES => [
        'error_codes' => \Phrest\Application::createRoute(
            '/your/path/to/error_codes', 
            \Phrest\Application::ACTION_ERROR_CODES
        ), 
    ],
];
```
Now call [http://localhost/your/path/to/error_codes](http://localhost/your/path/to/error_codes) to see your error codes. 
### Using your own error codes
You can tell phrest what error codes class to use. Just register your ErrorCodes class under ```\Phrest\Application::CONFIG_ERROR_CODES```. Phrest uses error codes from 0 to 1000. To avoid conflicts you should use ```LAST_PHREST_ERROR_CODE``` as base for your own error codes. 
```php
<?php
namespace Application;
class ErrorCodes extends \Phrest\API\ErrorCodes
{
    const MY_OWN_ERROR = self::LAST_PHREST_ERROR_CODE + 1;
    const MY_OWN_ERROR_2 = self::LAST_PHREST_ERROR_CODE + 2;
}
```
```php
<?php
// your configuration file
return [
    \Phrest\Application::CONFIG_ERROR_CODES => \Application\ErrorCodes::class,
    
    \Phrest\Application::CONFIG_DEPENDENCIES => [
        'invokables' => [
            \Application\ErrorCodes::class => \Application\ErrorCodes::class,
        ]
    ],
];
```
Now call [http://localhost/your/path/to/error_codes](http://localhost/your/path/to/error_codes) and you should see the phrest error codes and your own error codes.

## Todos
- ReadMe
- UnitTests
- add OpenAPI Spec 3.0 support (as soon as zircote/swagger-php 3.0 is released)
- HAL Links (now part of OpenAPI Spec 3.0)
    - [OpenAPI Spec 3.0 - Link Object](https://github.com/OAI/OpenAPI-Specification/blob/master/versions/3.0.0.md#linkObject)
- solve todos in code
- check cache speed and need (filesystem access cost vs cachable process cost)
    - granular user config for caching? (cache swagger: yes, cache error codes: no, ...)
    - user cache adapter for Phrest cache?
    
