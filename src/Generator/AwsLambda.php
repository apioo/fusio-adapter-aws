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

namespace Fusio\Adapter\Aws\Generator;

use Aws\Sdk;
use Fusio\Adapter\Aws\Action\AwsLambdaInvoke;
use Fusio\Engine\ConnectorInterface;
use Fusio\Engine\Factory\Resolver\PhpClass;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\Generator\ProviderInterface;
use Fusio\Engine\Generator\SetupInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Model\Backend\ActionConfig;
use Fusio\Model\Backend\ActionCreate;

/**
 * AwsLambda
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class AwsLambda implements ProviderInterface
{
    private ConnectorInterface $connector;

    public function __construct(ConnectorInterface $connector)
    {
        $this->connector = $connector;
    }

    public function getName(): string
    {
        return 'AWS-Lambda';
    }

    public function setup(SetupInterface $setup, ParametersInterface $configuration): void
    {
        $connection = $this->getConnection($configuration->get('connection'));
        $functions = $connection->createLambda()->listFunctions();

        foreach ($functions as $function) {
            $action = new ActionCreate();
            $action->setName($function->FunctionName);
            $action->setClass(AwsLambdaInvoke::class);
            $action->setConfig(ActionConfig::fromArray([
                'connection' => $configuration->get('connection'),
                'function_name' => $function->FunctionName,
            ]));
            $setup->addAction($action);
        }
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The AWS connection which should be used'));
    }

    private function getConnection(string $connectionId): Sdk
    {
        $connection = $this->connector->getConnection($connectionId);
        if ($connection instanceof Sdk) {
            return $connection;
        } else {
            throw new \RuntimeException('Invalid selected connection');
        }
    }
}
