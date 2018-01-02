<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2017 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fusio\Adapter\Amazon\Tests\Action;

use Aws\Lambda\LambdaClient;
use Aws\Result;
use Fusio\Adapter\Amazon\Action\AmazonLambda;
use Fusio\Adapter\Amazon\Connection\Lambda;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Form\Element;
use Fusio\Engine\Model\Connection;
use Fusio\Engine\ResponseInterface;
use Fusio\Engine\Test\CallbackConnection;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PSX\Record\Record;

/**
 * AmazonLambdaTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class AmazonLambdaTest extends \PHPUnit_Framework_TestCase
{
    use EngineTestCaseTrait;

    public function testHandle()
    {
        $args = [
            'FunctionName' => 'foo',
            'InvocationType' => 'RequestResponse',
            'LogType' => 'None',
            'Payload' => json_encode(['foo' => 'bar']),
        ];

        $client = $this->getMockBuilder(LambdaClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['invoke'])
            ->getMock();

        $result = new Result([
            'StatusCode' => 200,
            'Payload' => json_encode(['foo' => 'bar']),
        ]);

        $client->expects($this->once())
            ->method('invoke')
            ->with($this->equalTo($args))
            ->will($this->returnValue($result));

        $connection = new Connection();
        $connection->setId(1);
        $connection->setName('foo');
        $connection->setClass(CallbackConnection::class);
        $connection->setConfig([
            'callback' => function() use ($client){
                return $client;
            },
        ]);

        $this->getConnectionRepository()->add($connection);

        $action = $this->getActionFactory()->factory(AmazonLambda::class);

        // handle request
        $response = $action->handle(
            $this->getRequest(
                'GET',
                ['foo' => 'bar'],
                ['foo' => 'bar'],
                ['Content-Type' => 'application/json'],
                Record::fromArray(['foo' => 'bar'])
            ),
            $this->getParameters([
                'connection' => 1,
                'function_name' => 'foo',
            ]),
            $this->getContext()
        );

        $actual = json_encode($response->getBody(), JSON_PRETTY_PRINT);
        $expect = <<<JSON
{
    "foo": "bar"
}
JSON;

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    public function testGetForm()
    {
        $action  = $this->getActionFactory()->factory(AmazonLambda::class);
        $builder = new Builder();
        $factory = $this->getFormElementFactory();

        $action->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());

        $elements = $builder->getForm()->getProperty('element');
        $this->assertEquals(6, count($elements));
        $this->assertInstanceOf(Element\Connection::class, $elements[0]);
        $this->assertInstanceOf(Element\Input::class, $elements[1]);
        $this->assertInstanceOf(Element\Select::class, $elements[2]);
        $this->assertInstanceOf(Element\Select::class, $elements[3]);
        $this->assertInstanceOf(Element\Input::class, $elements[4]);
        $this->assertInstanceOf(Element\TextArea::class, $elements[5]);
    }
}
