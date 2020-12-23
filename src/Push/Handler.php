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

namespace Fusio\Impl\Provider\Push\Aws;

use Bref\Context\Context;
use Bref\Event\Http\HttpHandler;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\HttpResponse;
use Fusio\Engine\Serverless\ExecutorInterface;

/**
 * Handler
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class Handler extends HttpHandler
{
    /**
     * @var ExecutorInterface
     */
    private $executor;

    /**
     * @var array
     */
    private $method;

    public function __construct(ExecutorInterface $executor, array $method)
    {
        $this->executor = $executor;
        $this->method = $method;
    }

    public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse
    {
        $uriParameters = []; // @TODO get uri path parameters

        $response = $this->executor->execute(
            $this->method,
            $event->getMethod(),
            $event->getUri(),
            $uriParameters,
            $event->getHeaders(),
            $event->getBody()
        );

        return new HttpResponse((string) $response->getBody(), $response->getHeaders(), $response->getStatusCode());
    }
}
