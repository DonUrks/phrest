<?php

namespace Phrest\API;

use PHPUnit\Framework\TestCase;
use Zend\Diactoros\ServerRequest;

class RESTActionTraitTestClassBase
{
    use RESTActionTrait;
}

class RESTActionTraitTestClass extends RESTActionTraitTestClassBase
{
    public function get()
    {

    }

    public function delete()
    {

    }
}

class RESTActionTraitTest extends TestCase
{
    public function testConstructor()
    {
        $class = new RESTActionTraitTestClass();
        self::assertInstanceOf(RESTActionTraitTestClass::class, $class);
    }

    private function createClass(\Monolog\Handler\HandlerInterface $handler): RESTActionTraitTestClass
    {
        $class = new RESTActionTraitTestClass();
        $class->setLogger(
            new \Monolog\Logger('test', [$handler])
        );
        return $class;
    }

    public function testProcessMethodNotExists()
    {
        self::expectException(\Phrest\Http\Exception::class);
        self::expectExceptionMessage('Method not allowed');
        self::expectExceptionCode(405);

        $testHandler = new \Monolog\Handler\TestHandler();

        $class = $this->createClass($testHandler);
        try {
            $class->process(
                new \Zend\Diactoros\ServerRequest(
                    [],
                    [],
                    null,
                    'thisMethodDoesNotExists',
                    'php://memory'
                ),
                new \Zend\Expressive\Delegate\NotFoundDelegate(
                    new \Zend\Diactoros\Response()
                )
            );
        } catch (\Phrest\Http\Exception $e) {
            self::assertArraySubset([
                [
                    'message' => 'Phrest\API\RESTActionTrait::process called',
                    'context' => [
                        'method' => 'thismethoddoesnotexists'
                    ],
                    'level' => 100,
                    'level_name' => 'DEBUG',
                    'channel' => 'test',
                ]
            ], $testHandler->getRecords());

            self::assertEquals(0, $e->error()->code());
            self::assertEquals('Method not allowed', $e->error()->message());

            $errors = $e->error()->errors();
            self::assertCount(1, $errors);
            self::assertEquals(0, $errors[0]->code());
            self::assertEquals('Method "thismethoddoesnotexists" not allowed', $errors[0]->message());

            throw $e;
        }
    }

    public function testProcessOptions()
    {
        $testHandler = new \Monolog\Handler\TestHandler();

        $class = $this->createClass($testHandler);
        $response = $class->process(
            new \Zend\Diactoros\ServerRequest(
                [],
                [],
                null,
                'options',
                'php://memory'
            ),
            new \Zend\Expressive\Delegate\NotFoundDelegate(
                new \Zend\Diactoros\Response()
            )
        );

        self::assertArraySubset([
            [
                'message' => 'Phrest\API\RESTActionTrait::process called',
                'context' => [
                    'method' => 'options'
                ],
                'level' => 100,
                'level_name' => 'DEBUG',
                'channel' => 'test',
            ]
        ], $testHandler->getRecords());

        self::assertEquals(204, $response->getStatusCode());
        self::assertEquals('', $response->getBody()->getContents());

        self::assertEquals([
            'Allow' => [
                'GET, DELETE'
            ]
        ], $response->getHeaders());
    }

    public function testOnRESTRequest()
    {
        $testHandler = new \Monolog\Handler\TestHandler();

        $class = $this->createClass($testHandler);
        $response = $class->process(
            new \Zend\Diactoros\ServerRequest(
                [],
                [],
                null,
                'GET',
                'php://memory'
            ),
            new \Zend\Expressive\Delegate\NotFoundDelegate(
                new \Zend\Diactoros\Response()
            )
        );

        self::assertArraySubset([
            [
                'message' => 'Phrest\API\RESTActionTrait::process called',
                'context' => [
                    'method' => 'get'
                ],
                'level' => 100,
                'level_name' => 'DEBUG',
                'channel' => 'test',
            ]
        ], $testHandler->getRecords());

        self::assertEquals(204, $response->getStatusCode());
        self::assertEquals('', $response->getBody()->getContents());
    }
}
