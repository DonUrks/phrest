[![Build Status](https://travis-ci.org/DonUrks/phrest.svg?branch=master)](https://travis-ci.org/DonUrks/phrest)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/DonUrks/phrest/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/DonUrks/phrest/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/DonUrks/phrest/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/DonUrks/phrest/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/donurks/phrest/v/stable)](https://packagist.org/packages/donurks/phrest)
[![Total Downloads](https://poser.pugx.org/donurks/phrest/downloads)](https://packagist.org/packages/donurks/phrest)
[![License](https://poser.pugx.org/donurks/phrest/license)](https://packagist.org/packages/donurks/phrest)

# phrest

A PHP framework for building RESTful APIs with JSON and Swagger support. Phrest will automatically scan your code for swagger or HATEOAS annotations. If desired phrest will use the scanned swagger annotations for request data validation (see [AbstractSwaggerValidatorAction](#abstractswaggervalidatoraction)). 

## Features
- Swagger 2.0 definitions in annotations ([zircote/swagger-php](https://github.com/zircote/swagger-php))
- HATEOAS response definitions in annotations ([willdurand/Hateoas](https://github.com/willdurand/Hateoas))
- Request data validation against swagger data ([justinrainbow/json-schema](https://github.com/justinrainbow/json-schema))
- PSR-3 Logging ([Seldaek/monolog](https://github.com/Seldaek/monolog))
- Expandable with PSR-15 middleware
- PSR-7 Messages
- Error codes for API consumers
- Unified exception handling for HTTP status codes

## Requirements
- PHP 7.1
- Understanding zircote/swagger-php annotations
- Understanding willdurand/Hateoas annotations

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
---|---|---|---
applicationName | ```string``` | phrest-application | The name of your application. Used for Logging.
configDirectoryPattern | ```string``` | ```config/{{,*.}global,{,*.}local}.php``` | The glob pattern used for loading and merging your config files.
request | ```\Psr\Http\Message\ServerRequestInterface``` | ```ServerRequestFactory::fromGlobals()``` | If you want to provide your own request object instead of using the global variables. Useful for unit testing.

## Configuration
By default phrest will look at your ```config/``` directory and will load and merge all config files in the following order:
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
---|---|---
```CONFIG_ENABLE_CACHE``` | ```boolean``` | If true, phrest will cache swagger, HATEOAS and configurations. If true ```CONFIG_CACHE_DIRECTORY``` is **required**! 
```CONFIG_CACHE_DIRECTORY``` | ```string``` | The directory where phrest will cache. Make sure this directory is phrest exclusive to avoid conflicts (```cache/phrest/``` is a good choice).
```CONFIG_SWAGGER_SCAN_DIRECTORY``` | ```string``` | Tells phrest where to look for your swagger annotations. Usually this is your ```src/``` directory.
```CONFIG_DEPENDENCIES``` | ```array``` | Phrest uses the [zend-servicemanager](https://zendframework.github.io/zend-servicemanager/). Place your zend-servicemanager config here.
```CONFIG_ROUTES``` | ```array['path' => string, 'action' => string]``` | Bring path and action together. Each action must be a string refer to a service defined in ```CONFIG_DEPENDENCIES```. Must implement ```\Interop\Http\ServerMiddleware\MiddlewareInterface``` - or just use phrest [Abstract actions](#abstract-actions). See also [Routing](#routing).
```CONFIG_MONOLOG_HANDLER``` | ```string[]``` | You can register one or more Monolog handlers. Each string must refer to a service defined in ```CONFIG_DEPENDENCIES```.
```CONFIG_MONOLOG_PROCESSOR``` | ```string[]``` | You can register one or more Monolog processors. Each string must refer to a service defined in ```CONFIG_DEPENDENCIES```.
```CONFIG_ERROR_CODES``` | ```string``` | Tells phrest to use your error codes class. String must refer to a service defined in ```CONFIG_DEPENDENCIES```. Must extends ```\Phrest\API\ErrorCodes```. See [Using your own error codes](#using-your-own-error-codes).
```CONFIG_PRE_ROUTING_MIDDLEWARE``` | ```string[]``` | Register your own middleware called before routing. Each string must refer to a service defined in ```CONFIG_DEPENDENCIES```. Must implements ```\Interop\Http\ServerMiddleware\MiddlewareInterface```.
```CONFIG_PRE_DISPATCHING_MIDDLEWARE``` | ```string[]``` | Register your own middleware called before dispatching the action. Each string must refer to a service defined in ```CONFIG_DEPENDENCIES```. Must implements ```\Interop\Http\ServerMiddleware\MiddlewareInterface```.

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
---|---|---
```SERVICE_LOGGER``` | ```\Psr\Log\LoggerInterface``` | Writes log entries to all registered ```\Phrest\Application::CONFIG_MONOLOG_HANDLER```
```SERVICE_ROUTER``` | ```\Zend\Expressive\Router\RouterInterface``` | The router used to determine the action.
```SERVICE_SWAGGER``` | ```\Phrest\Swagger``` | The phrest swagger abstraction.
```SERVICE_HATEOAS``` | ```\Hateoas\Hateoas``` | The HATEOAS serializer / deserializer.
```SERVICE_HATEOAS_RESPONSE_GENERATOR``` | ```\Phrest\API\HateoasResponseGenerator``` | Can be used to generate json response with the help of willdurand/Hateoas. See [HATEOAS response generator](#hateoas-response-generator)
```SERVICE_REQUEST_SWAGGER_VALIDATOR``` | ```\Phrest\API\RequestSwaggerValidator``` | Can be used to validate request data against swagger schema. See [Request swagger validator](#request-swagger-validator)   

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
```ACTION_SWAGGER``` | Provides the swagger file in json format.
```ACTION_ERROR_CODES``` | Provides all possible error codes in json format. See [Error Codes](#error-codes).

## Routing
A route is connection between a path and an action. Use the ```\Phrest\Application::CONFIG_ROUTES``` configuration to add routes.
There is also a static method ```\Phrest\Application::createRoute()``` which creates a route entry.

The array keys are used to name the route. The route names are used in the [HATEOAS response generator](#hateoas-response-generator) for link generation.

You can also provide a mapping for operation ids (see [AbstractSwaggerValidatorAction](#abstractswaggervalidatoraction)). 

```php#abstractswaggervalidatoraction
<?php
// your config file
return [
    \Phrest\Application::CONFIG_ROUTES => [
        'the-name-of-your-route' => \Phrest\Application::createRoute(
            '/the-path-of-your-route',
            
            // Your action - must be refer to a service in your CONFIG_DEPENDENCIES
            \YourAction::class,
            [
                // AbstractSwaggerValidatorAction will now use "someOperationId" instead of "get.the-name-of-your-route"
                'get' => 'someOperationId'    
            ]
        ),
    ],
];
```

## Abstract actions
You can write your own actions by implementing the ```\Interop\Http\ServerMiddleware\MiddlewareInterface```. Or you can use the abstract actions provided by phrest.

### AbstractAction
Use this abstract action if you just want to map the HTTP methods to action methods. 

Extend the ```\Phrest\API\AbstractAction``` class and overwrite the methods as needed.
If phrest receives an request with a method not provided by your action, phrest will handle the error response automatically.

Method | Parameter | Return type | Description
---|---|---|---
```get```, ```put```, ```post```, ```patch```, ```delete``` | ```\Psr\Http\Message\ServerRequestInterface``` | ```\Psr\Http\Message\ResponseInterface``` or null | If your method method returns ```null```, phrest will generate an empty response with http status code 204.
```options``` | - | - | You can't overwrite the ```options``` method. Phrest will automatically generate a response with all allowed (=implemented) methods. 

```php
<?php
class Test extends \Phrest\API\AbstractAction
{
    public function get(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        return new \Zend\Diactoros\Response\JsonResponse(['name' => 'some name']);
    }
}
```

### AbstractSwaggerValidatorAction
Use this abstract action if you want phrest to validate your request based on swagger annotations. 

Phrest will use the current route name to validate all request parameters defined in your swagger annotations.
By default, phrest will use a operationId with the pattern: "method.route-name" ("get.name-of-your-route").

You can overwrite the operationIds for each method (see [Routing](#routing)).
 
The operationId defined in the route have to match with the operationId in the swagger annotations.

If validation failed, phrest will handle the error response automatically.

Extend the ```\Phrest\API\AbstractSwaggerValidatorAction``` class and overwrite the methods as needed.
If phrest receives a request with a method not provided by your actions, a ```\Phrest\Http\Exception``` will be thrown resulting in a http status 405 with error model body response (see [Exceptions](#exceptions)).

Method | Parameter | Return type | Description
---|---|---|---
```get```, ```put```, ```post```, ```patch```, ```delete``` | ```\Psr\API\RequestSwaggerData``` | ```\Psr\Http\Message\ResponseInterface``` or null | If your method method returns ```null```, phrest will generate an empty response with http status code 204.
```options``` | - | - | You can't overwrite the ```options``` method. Phrest will automatically generate a response with all allowed (=implemented) methods. 

Use the ```\Phrest\API\RequestSwaggerData``` object to access your request parameters. See [Request swagger validator](#request-swagger-validator) for details.

```php
<?php
// your config file
return [
    \Phrest\Application::CONFIG_ROUTES => [
        // The route name "someRouteName" matches operationId in the swagger annotations
        'someRouteName' => \Phrest\Application::createRoute(
            '/some-path', 
            \SomeAction::class
        ),
    ],
];
```

```php
<?php
// your action
class SomeAction extends \Phrest\API\AbstractSwaggerValidatorAction
{
    /**
     * @SWG\Get(
     *     path="/some-path",
     *     operationId="someRouteName",
     *     @SWG\Parameter(
     *          name="id",
     *          in="query",
     *          type="number"
     *     ),
     *     @SWG\Response(response="200", description="Success")
     * )
     *
     * @param \Phrest\API\RequestSwaggerData $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function get(\Phrest\API\RequestSwaggerData $data): \Psr\Http\Message\ResponseInterface
    {
        return new \Zend\Diactoros\Response\JsonResponse(
            ['id' => $data->getQueryValues()['id']]
        );
    }
}
```

## Request swagger validator
You can use the request swagger validator to validate your request parameters against your swagger operations. 
Just provide your request object and swagger operationId. 
Phrest will use all parameter definitions provided in the operation linked by the operation Id. 

You can use the service ```\Phrest\Application::SERVICE_REQUEST_SWAGGER_VALIDATOR``` to inject the request swagger validator to your classes.

```php
<?php
// your config file
return [
    \Phrest\Application::CONFIG_DEPENDENCIES => [
        'factories' => [
            \SomeAction::class => function (\Interop\Container\ContainerInterface $container) {
                return new \SomeAction(
                    $container->get(\Phrest\Application::SERVICE_REQUEST_SWAGGER_VALIDATOR)
                );
            },
        ]
    ],
];
```

You can also implement the ```\Phrest\API\RequestSwaggerValidatorAwareInterface``` and use the ```\Phrest\API\RequestSwaggerValidatorAwareTrait```.
Phrest will automatically inject the request swagger validator to your class und populate a ```requestSwaggerValidator``` property.

```php
<?php
// your action
class SomeAction extends \Phrest\API\AbstractAction
    implements \Phrest\API\RequestSwaggerValidatorAwareInterface
{
    use \Phrest\API\RequestSwaggerValidatorAwareTrait;
    
    public function get(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $data = $this->requestSwaggerValidator->validate($request, 'someOperationId');
        return new \Zend\Diactoros\Response\EmptyResponse();
    }
}
```

Or just use the [AbstractSwaggerValidatorAction](#abstractswaggervalidatoraction) and phrest will take care of fetching the right operationId.

The validate method takes two parameters: the request object and the operationId.

Phrest will look in your swagger for the given operationId. If there is no operation for this id, phrest will throw an ```\Phrest\Exception``` resulting in a http status 500 response if not catched.
If the validation failed, phrest will throw an ```\Phrest\Http\Exception``` resulting in a http status 400 response with error model if not catched.

If validation succeed, the validate method will return an ```\Phrest\API\RequestSwaggerData``` object.

The ```\Phrest\API\RequestSwaggerData``` object contains all parameters validated, filled with default values (if defined) and correct data types (as definded in swagger annotations).

\Phrest\API\RequestSwaggerData method | Description | Request example | Value
---|---|---|---
```getBodyValue()``` | Returns the parsed json object. | {"name": "Batman"} | {stdClass json object}
```getQueryValues()``` | Returns all query params as key value pairs. | your-url?var1=value1 | ['var1' => 'value1']
```getPathValues()``` | Returns all path params as key value pairs. | your-url/somePath/{pathVar} | ['pathVar' => '{value}']
```getHeaderValues()``` |Returns all header params as key value pairs. | SOME_HEADER=some-value | ['SOME_HEADER' => 'some-value']

For swagger-php details see [zircote/swagger-php](https://github.com/zircote/swagger-php).

For swagger details see [OpenAPI Spec 2.0](https://github.com/OAI/OpenAPI-Specification/blob/3.0.0/versions/2.0.md). 

## HATEOAS response generator
The HATEOAS response generator will generate a JSON response from your objects with the help of annotations ([willdurand/Hateoas](https://github.com/willdurand/Hateoas)).

You can use the service ```\Phrest\Application::SERVICE_HATEOAS_RESPONSE_GENERATOR``` to inject the hateoas response generator to your classes.

```php
<?php
// your config file
return [
    \Phrest\Application::CONFIG_DEPENDENCIES => [
        'factories' => [
            \SomeAction::class => function (\Interop\Container\ContainerInterface $container) {
                return new \SomeAction(
                    $container->get(\Phrest\Application::SERVICE_HATEOAS_RESPONSE_GENERATOR)
                );
            },
        ]
    ],
];
```

You can also implement the ```\Phrest\API\HateoasResponseGeneratorAwareInterface``` and use the ```\Phrest\API\HateoasResponseGeneratorAwareTrait```.
Phrest will automatically inject the response generator to your class und populate a ```generateHateoasResponse``` method.

```php
<?php
// your action
class SomeAction extends \Phrest\API\AbstractAction
    implements \Phrest\API\HateoasResponseGeneratorAwareInterface
{
    use \Phrest\API\HateoasResponseGeneratorAwareTrait;
    
    public function get(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $user = new User(
            453, 
            'Bruce',
            'Wayne'
        );
        return $this->generateHateoasResponse($user);
    }
}
```

The ```generateHateoasResponse``` method takes your object and optionally a http status code and a headers array.

You can use the HATEOAS route in relation annotations to generate links. Just pass the name of the route (see [Routing](#routing)). You can also pass named path parameters for url generation.

```php
<?php
// your config file
return [
    \Phrest\Application::CONFIG_ROUTES => [
        'your-route' => \Phrest\Application::createRoute(
            '/users/{userId}', 
            \YourAction::class
        ),
    ],
];
```

```php
<?php
/**
 * @Hateoas\Configuration\Annotation\Relation(
 *      "self",
 *      href = @Hateoas\Configuration\Annotation\Route(
 *          "your-route",
 *          parameters = { "userId" = "expr(object.getId())" },
 *          absolute = true
 *      )
 * )
 */
class User
{
    private $id;
    private $first_name;
    private $last_name;

    public function __construct($id, $first_name, $last_name)
    {
        $this->id = $id;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
    }

    public function getId() {
        return $this->id;
    }
}
```

The resulting output should now look like this:
```json
{
  "id": 453,
  "first_name": "Bruce",
  "last_name": "Wayne",
  "_links": {
    "self": {
      "href": "http://localhost/users/453"
    }
  }
}
```

For willdurand/Hateoas details see [willdurand/Hateoas](https://github.com/willdurand/Hateoas). 

## Logging
You can use the service ```\Phrest\Application::SERVICE_LOGGER``` to inject the logger to your classes.

```php
<?php
// your config file
return [
    \Phrest\Application::CONFIG_DEPENDENCIES => [
        'factories' => [
            \SomeAction::class => function (\Interop\Container\ContainerInterface $container) {
                return new \SomeAction(
                    $container->get(\Phrest\Application::SERVICE_LOGGER)
                );
            },
        ]
    ],
];
```

You can also implement the ```\Psr\Log\LoggerAwareInterface``` and use the ```\Psr\Log\LoggerAwareTrait```.
Phrest will automatically inject the logger to your class und populate a ```logger``` property.

```php
<?php
// your action
class SomeAction extends \Phrest\API\AbstractAction
    implements \Psr\Log\LoggerAwareInterface
{
    use \Psr\Log\LoggerAwareTrait;
    
    public function get(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $this->logger->info('a log message');
        return new \Zend\Diactoros\Response\EmptyResponse();
    }
}
```

For handler and processor details see [Monolog](https://github.com/Seldaek/monolog).

## Exceptions
Phrest will generate a response with http status code 500 for every unhandled exception.

Except for the ```\Phrest\Http\Exception```. If not catched, phrest will generate a response with correct http status code and error model body.

Every ```\Phrest\Http\Exception``` needs a http status code and an error model with error entries.

The error codes on ```\Phrest\API\Error``` and ```\Phrest\API\ErrorEntry``` should be used from ```\Phrest\API\ErrorCodes``` or your own error codes class (see [Error Codes](#error-codes)).

```php
<?php
// all alone
throw new \Phrest\Http\Exception(
    400,
    new \Phrest\API\Error(
        1,
        'Request parameter validation error',
        new \Phrest\API\ErrorEntry(
            2,
            '{query}/id',
            'Value must be a number, string given',
            'is_number'
        )
    )
);

// with short hand method
throw \Phrest\Http\Exception::Unauthorized(
    new \Phrest\API\Error(
        1,
        'Request parameter validation error',
        new \Phrest\API\ErrorEntry(
            2,
            '{query}/id',
            'Value must be a number, string given',
            'is_number'
        )
    )
);

// with error codes class
throw \Phrest\Http\Exception::Unauthorized(
    new \Phrest\API\Error(
        \Phrest\API\ErrorCodes::REQUEST_PARAMETER_VALIDATION,
        'Request parameter validation error',
        new \Phrest\API\ErrorEntry(
            \Phrest\API\ErrorCodes::REQUEST_VALIDATION_TYPE,
            '{query}/id',
            'Value must be a number, string given',
            'is_number'
        )
    )
);
```

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
- ReadMe (some links doesnt work)
- UnitTests
- add OpenAPI Spec 3.0 support (as soon as zircote/swagger-php 3.0 is released)
- HAL Links (part of OpenAPI Spec 3.0)
    - [OpenAPI Spec 3.0 - Link Object](https://github.com/OAI/OpenAPI-Specification/blob/master/versions/3.0.0.md#linkObject)
- solve todos in code
- check cache speed and need (filesystem access cost vs cachable process cost)
    - granular user config for caching? (cache swagger: yes, cache error codes: no, ...)
- injectable PSR-16 (\Psr\SimpleCache\CacheInterface) cache-adapter when zend-cache 2.8.0 released [2.8.0](https://github.com/zendframework/zend-cache/milestone/12)
    
