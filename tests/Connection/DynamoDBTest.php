<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2018 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Adapter\Amazon\Tests\Connection;

use Aws\DynamoDb\DynamoDbClient;
use Fusio\Adapter\Amazon\Connection\DynamoDB;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Form\Element\Input;
use Fusio\Engine\Parameters;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PHPUnit\Framework\TestCase;

/**
 * DynamoDBTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class DynamoDBTest extends TestCase
{
    use EngineTestCaseTrait;

    public function testGetConnection()
    {
        /** @var DynamoDB $connectionFactory */
        $connectionFactory = $this->getConnectionFactory()->factory(S3::class);

        $config = new Parameters([
            'version' => '2006-03-01',
            'region'  => 'us-west-2',
        ]);

        $connection = $connectionFactory->getConnection($config);

        $this->assertInstanceOf(DynamoDbClient::class, $connection);
    }

    public function testConfigure()
    {
        $connection = $this->getConnectionFactory()->factory(S3::class);
        $builder    = new Builder();
        $factory    = $this->getFormElementFactory();

        $connection->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());

        $elements = $builder->getForm()->getProperty('element');
        $this->assertEquals(4, count($elements));
        $this->assertInstanceOf(Input::class, $elements[0]);
        $this->assertInstanceOf(Input::class, $elements[1]);
        $this->assertInstanceOf(Input::class, $elements[2]);
        $this->assertInstanceOf(Input::class, $elements[3]);
    }
}
