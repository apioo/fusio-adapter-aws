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
            throw new ConfigurationException('Given connection must be an Aws connection');
        }

        $functionName = $configuration->get('function_name');
        if (empty($functionName)) {
            throw new ConfigurationException('No function name provided');
        }

        $client = $sdk->createLambda();

        $args = [
            'FunctionName' => $functionName,
            'InvocationType' => $configuration->get('invocation_type') ?: 'RequestResponse',
            'LogType' => $configuration->get('log_type') ?: 'None',
            'Payload' => json_encode($request->getPayload()),
        ];

        $clientContext = $configuration->get('client_context');
        if (!empty($clientContext)) {
            $args['ClientContext'] = $clientContext;
        }

        $result = $client->invoke($args);

        return $this->response->build(
            (int) $result->get('StatusCode'),
            [],
            json_decode($result->get('Payload') ?: '{}')
        );
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $invocationTypes = [
            'Event' => 'Event',
            'RequestResponse' => 'Request-Response',
            'DryRun' => 'Dry-Run',
        ];

        $logTypes = [
            'None' => 'None',
            'Tail' => 'Tail',
        ];

        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The Amazon connection'));
        $builder->add($elementFactory->newInput('function_name', 'FunctionName', 'text', 'The Lambda function name.'));
        $builder->add($elementFactory->newSelect('invocation_type', 'Invocation-Type', $invocationTypes, 'By default, the Invoke API assumes "RequestResponse" invocation type. You can optionally request asynchronous execution by specifying "Event" as the InvocationType. You can also use this parameter to request AWS Lambda to not execute the function but do some verification, such as if the caller is authorized to invoke the function and if the inputs are valid. You request this by specifying "DryRun" as the InvocationType. This is useful in a cross-account scenario when you want to verify access to a function without running it.'));
        $builder->add($elementFactory->newSelect('log_type', 'Log-Type', $logTypes, 'You can set this optional parameter to "Tail" in the request only if you specify the InvocationType parameter with value "RequestResponse". In this case, AWS Lambda returns the base64-encoded last 4 KB of log data produced by your Lambda function in the x-amz-log-results header.'));
        $builder->add($elementFactory->newTextArea('client_context', 'Client-Context', 'json', 'Using the ClientContext you can pass client-specific information to the Lambda function you are invoking. You can then process the client information in your Lambda function as you choose through the context variable.'));
    }
}
