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

namespace Fusio\Adapter\Aws\Introspection;

use Aws\Sdk;
use Fusio\Engine\Connection\Introspection\Entity;
use Fusio\Engine\Connection\Introspection\IntrospectorInterface;
use Fusio\Engine\Connection\Introspection\Row;

/**
 * Introspector
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class Introspector implements IntrospectorInterface
{
    private Sdk $sdk;

    public function __construct(Sdk $sdk)
    {
        $this->sdk = $sdk;
    }

    public function getEntities(): array
    {
        $functions = $this->sdk->createLambda()->listFunctions();

        $names = [];
        foreach ($functions as $function) {
            $names[] = $function->FunctionName;
        }

        return $names;
    }

    public function getEntity(string $entityName): Entity
    {
        $function = $this->sdk->createLambda()->getFunction([
            'FunctionName' => $entityName
        ]);

        $values = [
            'FunctionName' => $entityName,
            'Configuration' => json_encode($function->get('Configuration'), JSON_PRETTY_PRINT),
            'Code' => json_encode($function->get('Code'), JSON_PRETTY_PRINT),
        ];

        $entity = new Entity($entityName, ['Key', 'Value']);
        foreach ($values as $key => $value) {
            $entity->addRow(new Row([
                $key,
                $value,
            ]));
        }

        return $entity;
    }
}
