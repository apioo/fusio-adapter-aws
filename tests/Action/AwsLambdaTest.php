<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2023 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Adapter\Aws\Tests\Action;

use Aws\Lambda\LambdaClient;
use Aws\Result;
use Aws\Sdk;
use Fusio\Adapter\Aws\Action\AwsLambdaInvoke;
use Fusio\Adapter\Aws\Tests\AwsTestCase;
use Fusio\Engine\Model\Connection;
use Fusio\Engine\Test\CallbackConnection;
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Record\Record;

/**
 * AwsLambdaTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class AwsLambdaTest extends AwsTestCase
{
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

        $sdk = $this->getMockBuilder(Sdk::class)
            ->disableOriginalConstructor()
            ->setMethods(['createLambda'])
            ->getMock();

        $sdk->expects($this->once())
            ->method('createLambda')
            ->will($this->returnValue($client));

        $connection = new Connection(1, 'foo', CallbackConnection::class, [
            'callback' => function() use ($sdk){
                return $sdk;
            },
        ]);

        $this->getConnectionRepository()->add($connection);

        $action = $this->getActionFactory()->factory(AwsLambdaInvoke::class);

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

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }
}
