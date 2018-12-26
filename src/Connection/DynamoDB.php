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

namespace Fusio\Adapter\Amazon\Connection;

use Aws\S3\S3Client;
use Fusio\Engine\ParametersInterface;

/**
 * DynamoDB
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class DynamoDB extends ConnectionAbstract
{
    public function getName()
    {
        return 'Amazon-DynamoDB';
    }

    /**
     * @param \Fusio\Engine\ParametersInterface $config
     * @return \Aws\DynamoDB\DynamoDbClient
     */
    public function getConnection(ParametersInterface $config)
    {
        return $this->newSdk($config)->createDynamoDb();
    }
}
