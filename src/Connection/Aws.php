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

namespace Fusio\Adapter\Aws\Connection;

use Aws\Sdk;
use Fusio\Engine\ConnectionAbstract;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;

/**
 * Aws
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class Aws extends ConnectionAbstract
{
    public function getName(): string
    {
        return 'AWS';
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
