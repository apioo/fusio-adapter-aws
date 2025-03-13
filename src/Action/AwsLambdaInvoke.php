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

namespace Fusio\Adapter\Aws\Action;

use Aws\Sdk;
use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Json\Parser;

/**
 * AwsLambdaInvoke
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class AwsLambdaInvoke extends ActionAbstract
{
    public function getName(): string
    {
        return 'AWS-Lambda-Invoke';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): HttpResponseInterface
    {
        $sdk = $this->connector->getConnection($configuration->get('connection'));
        if (!$sdk instanceof Sdk) {
            throw new ConfigurationException('Given connection must be an AWS connection');
        }

        $args = [
            'FunctionName' => $configuration->get('function_name'),
            'InvocationType' => 'RequestResponse',
            'LogType' => 'None',
            'Payload' => Parser::encode([
                'arguments' => $request->getArguments(),
                'payload' => $request->getPayload(),
                'context' => $request->getContext(),
            ]),
            'ClientContext' => base64_encode(Parser::encode($context)),
        ];

        $result = $sdk->createLambda()->invoke($args);

        return $this->response->build(
            (int) $result->get('StatusCode'),
            [],
            Parser::decode($result->get('Payload') ?? '{}')
        );
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The AWS connection'));
        $builder->add($elementFactory->newConnection('function_name', 'Function Name', 'The lambda function name'));
    }
}
