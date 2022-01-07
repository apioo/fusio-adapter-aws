<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2022 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Adapter\Aws\Connection;

use Aws\Sdk;
use Fusio\Engine\ConnectionInterface;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;

/**
 * Aws
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class Aws implements ConnectionInterface
{
    public function getName(): string
    {
        return 'Aws';
    }

    public function getConnection(ParametersInterface $config): Sdk
    {
        $params = [
            'version' => $config->get('version'),
            'region'  => $config->get('region'),
        ];

        $key    = $config->get('key');
        $secret = $config->get('secret');
        if (!empty($key) && !empty($secret)) {
            $params['credentials'] = [
                'key'    => $key,
                'secret' => $secret,
            ];
        }

        return new Sdk($params);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newInput('version', 'Version', 'The version of the webservice to utilize (e.g., 2006-03-01)'));
        $builder->add($elementFactory->newInput('region', 'Region', 'Region to connect to. See http://docs.aws.amazon.com/general/latest/gr/rande.html for a list of available regions'));
        $builder->add($elementFactory->newInput('key', 'Key', 'AWS access key ID'));
        $builder->add($elementFactory->newInput('secret', 'Secret', 'AWS secret access key'));
    }
}
