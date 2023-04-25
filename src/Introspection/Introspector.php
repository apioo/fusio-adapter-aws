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

namespace Fusio\Adapter\Aws\Introspection;

use Aws\Sdk;
use Fusio\Engine\Connection\Introspection\Entity;
use Fusio\Engine\Connection\Introspection\IntrospectorInterface;
use Fusio\Engine\Connection\Introspection\Row;

/**
 * Introspector
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
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
