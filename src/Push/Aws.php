<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2020 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Adapter\Aws\Push;

use Fusio\Engine\Push\ProviderInterface;
use Fusio\Engine\Serverless\Config;
use Fusio\Engine\Serverless\GeneratorInterface;

/**
 * Aws
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class Aws implements ProviderInterface
{
    /**
     * @var GeneratorInterface
     */
    private $generator;

    /**
     * @param GeneratorInterface $generator
     */
    public function __construct(GeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @param string $basePath
     * @return \Generator
     */
    public function push(string $basePath)
    {
        $config = new Config();
        $config->setProviderName('aws');
        $config->setProviderRuntime('provided.al2');
        $config->setPlugins([
            './vendor/bref/bref'
        ]);
        $config->setPlugins([
            '${bref:layer.php-74-fpm}'
        ]);

        yield from $this->generator->generate(
            $basePath,
            $config,
            \Fusio\Adapter\Aws\Handler\Aws::class
        );
    }
}
