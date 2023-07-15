<?php
/*
 * Fusio is an open source API management platform which helps to create innovative API solutions.
 * For the current version and information visit <https://www.fusio-project.org/>
 *
 * Copyright 2015-2023 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
 * @license http://www.apache.org/licenses/LICENSE-2.0
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
