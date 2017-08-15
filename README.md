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

```sh
composer require donurks/phrest
```

## Basic usage
### Project structure
- create the following files and directories
    - public/
        - index.php
    - config/
        - global.php
    - cache/
        - phrest/
    - src/
        - YourAPI/
            - Action/
                - Message.php
            - Model/
                - Message.php
                
### config/global.php
```php
<?php
return [
    // tells phrest where to look for your annotations
    \Phrest\Application::CONFIG_SWAGGER_SCAN_DIRECTORY => 'src',
    
    // phrest cache directory
    \Phrest\Application::CONFIG_CACHE_DIRECTORY => 'cache/phrest',
    
    // enable, disable cache
    \Phrest\Application::CONFIG_ENABLE_CACHE => false,
    
    // phrest uses the zend service manager for dependency injection
    \Phrest\Application::CONFIG_DEPENDENCIES => [
        'factories' => [
            \YourAPI\Action\Message::class => function(\Interop\Container\ContainerInterface $container) {
                return new \YourAPI\Action\Message();
            },
        ]
    ],
    
    // tells phrest which action is used for each path
    \Phrest\Application::CONFIG_ROUTES => [
        'messages' => \Phrest\Application::createRoute('/messages', \YourAPI\Action\Message::class), 
    ],
];
```

### public/index.php
This is the entry point to your API. Make sure that your webservers document root points here.
```php
<?php
chdir(dirname(__DIR__));
require_once "vendor/autoload.php";

// start the API and use the configuration in config/
\Phrest\Application::run('YourAPI');
```
### src/YourAPI/Model/Message.php
```php
<?php

namespace Application\Model;

/**
 * @SWG\Definition(
 *      definition="Message",
 *      type="object",
 *      required={"id", "name"},
 *      @SWG\Property(
 *          property="id",
 *          type="number",
 *          format="int64"
 *      ),
 *      @SWG\Property(
 *          property="name",
 *          type="string"
 *      )
 *  )
 */

/**
 * @Hateoas\Configuration\Annotation\Relation("self", href = "expr('/api/messages/' ~ object.getId())")
 */
class Message
{
    private $id;
    private $name;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }
}
```

### src/YourAPI/Action/Message.php
This is your first action with response. All HTTP requests will end up in their HTTP method specific methods. 
```php
<?php

namespace YourAPI\Action;

/**
 * @SWG\Info(title="YourAPI", version="1.0")
 * @SWG\Get(
 *     path="/message",
 *     @SWG\Response(response="200", description="An example resource"),
 *     @SWG\Parameter(
 *      name="id",
 *      description="id",
 *      type="number",
 *      in="query",
 *      required=true
 *     ),
 *     @SWG\Parameter(
 *      name="name",
 *      description="Name",
 *      type="string",
 *      in="query",
 *      default="no name" 
 *     )
 * )
 */
class Message extends \Phrest\API\AbstractSwaggerValidatorAction 
                implements \Phrest\API\HateoasResponseGeneratorAwareInterface
{
    use \Phrest\API\HateoasResponseGeneratorAwareTrait;
    public function get(\Phrest\API\RequestSwaggerData $data): \Psr\Http\Message\ResponseInterface
    {
        $message = new \YourAPI\Model\Message(
            $data->getQueryValues()['id'],
            $data->getQueryValues()['name']
        );
        return $this->hateoasResponseGenerator->generate($message);
    }
}
```
        
### Autoload for YourAPI (Composer)
Don't forget to add the YourAPI namespace to the autoloader.
```json
{
    "autoload": {
        "psr-4": {
          "YourAPI\\": "src/YourAPI/"
        }
    }
}
```
```sh
composer install
```

### Call your API
You can use the internal php webserver to test your API:
```sh
php.exe -S 0.0.0.0:80 -t public
```

#### [http://localhost/messages](http://localhost/messages) 
This will show you an error. The required query parameter "id" is missing. Let's give it another try.

#### [http://localhost/messages?id=4](http://localhost/messages?id=4)
This should show no error.
 
## Advanced usage
### Phrest actions
#### Swagger
You can use the phrest swagger action to publish your swagger definitions for your API consumers.
```php
<?php
// your configuration file
return [
    \Phrest\Application::CONFIG_ROUTES => [
        'swagger' => \Phrest\Application::createRoute(
            '/your/path/to/swagger', 
            \Phrest\Application::ACTION_SWAGGER
        ), 
    ],
];
```
Now call [http://localhost/your/path/to/swagger](http://localhost/your/path/to/swagger) to see your swagger definitions. Ready to use with [Swagger UI](https://github.com/swagger-api/swagger-ui). 
#### Error codes
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
##### Using your own error codes
You can tell phrest what error codes class to use. Just provide a service named \Phrest\Application::CONFIG_ERROR_CODES. Phrest uses error codes from 0 to 1000. To avoid conflicts you should use LAST_PHREST_ERROR_CODE as base for your own error codes. 
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
    \Phrest\Application::CONFIG_DEPENDENCIES => [
        'factories' => [
            \Phrest\Application::CONFIG_ERROR_CODES => function () {
                return new \Application\ErrorCodes();
            },
        ]
    ],
];
```
Now call [http://localhost/your/path/to/error_codes](http://localhost/your/path/to/error_codes) and you should see the phrest error codes and your own error codes.

## Todos
- ReadMe
- UnitTests
- HAL Links (now part of OpenAPI Spec 3.0)
    - [OpenAPI Spec 3.0 - Link Object](https://github.com/OAI/OpenAPI-Specification/blob/master/versions/3.0.0.md#linkObject)
- solve todos in code
- add license information in src and composer.json
- check cache speed and need (filesystem access cost vs cachable process cost)
    - granular user config for caching? (cache swagger: yes, cache error codes: no, ...)
    - user cache adapter for Phrest cache?
    
