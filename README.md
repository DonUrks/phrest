# phrest

A php framework for your RESTful API with JSON and Swagger support. Phrest will automatically scan your code for swagger or HATEOAS annotations. If desired phrest will also use your scanned swagger annotations for request data validation.

## Features
- Swagger definition in annotations ([zircote/swagger-php](https://github.com/zircote/swagger-php))
- HATEOAS response definition in annotations ([willdurand/Hateoas](https://github.com/willdurand/Hateoas))
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
    \Phrest\Config::SWAGGER_SCAN_DIRECTORY => 'src',
    
    // phrest uses the zend service manager for dependency injection
    \Phrest\Config::DEPENDENCIES => [
        'factories' => [
            \YourAPI\Action\Message::class => function(\Interop\Container\ContainerInterface $container) {
                return new \YourAPI\Action\Message();
            },
        ]
    ],
    
    // tells phrest which action is used for each path
    \Phrest\Config::ROUTES => [
        'messages' => [
            'path' => '/messages',
            'action' => \YourAPI\Action\Message::class
        ],
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
 
## Todos
- ReadMe
- UnitTests
- HATEOAS absolute url (with custom URL Generator)
- HAL Links
- solve todos in code
- add license information in src and composer.json
- check cache speed and need (filesystem access cost vs cachable process cost)
    - granular user config for caching? (cache swagger: yes, cache error codes: no, ...)
    - user cache adapter for Phrest cache?