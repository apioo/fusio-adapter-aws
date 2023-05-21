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
use Fusio\Model\Backend\Action;
use Fusio\Model\Backend\ActionConfig;

/**
 * AwsLambda
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
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

    public function setup(SetupInterface $setup, string $basePath, ParametersInterface $configuration): void
    {
        $connection = $this->getConnection($configuration->get('connection'));
        $functions = $connection->createLambda()->listFunctions();

        foreach ($functions as $function) {
            $action = new Action();
            $action->setName($function->FunctionName);
            $action->setClass(AwsLambdaInvoke::class);
            $action->setEngine(PhpClass::class);
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
