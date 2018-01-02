<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2017 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Adapter\Amazon\Action;

use Aws\Lambda\LambdaClient;
use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;

/**
 * AmazonLambda
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class AmazonLambda extends ActionAbstract
{
    public function getName()
    {
        return 'Amazon-Lambda';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $client = $this->connector->getConnection($configuration->get('connection'));

        if ($client instanceof LambdaClient) {
            $args = [
                'FunctionName' => $configuration->get('function_name'),
                'InvocationType' => $configuration->get('invocation_type') ?: 'RequestResponse',
                'LogType' => $configuration->get('log_type') ?: 'None',
                'Payload' => json_encode($request->getBody()),
            ];

            $clientContext = $configuration->get('client_context');
            if (!empty($clientContext)) {
                $args['ClientContext'] = $clientContext;
            }

            $qualifier = $configuration->get('qualifier');
            if (!empty($qualifier)) {
                $args['Qualifier'] = $qualifier;
            }

            $result = $client->invoke($args);

            return $this->response->build(
                $result->get('StatusCode'),
                [],
                json_decode($result->get('Payload') ?: '{}')
            );
        } else {
            throw new ConfigurationException('Given connection must be a LambdaClient connection');
        }
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
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

        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The Amazon-Lambda connection'));
        $builder->add($elementFactory->newInput('function_name', 'FunctionName', 'text', 'The Lambda function name.'));
        $builder->add($elementFactory->newSelect('invocation_type', 'Invocation-Type', $invocationTypes, 'By default, the Invoke API assumes "RequestResponse" invocation type. You can optionally request asynchronous execution by specifying "Event" as the InvocationType. You can also use this parameter to request AWS Lambda to not execute the function but do some verification, such as if the caller is authorized to invoke the function and if the inputs are valid. You request this by specifying "DryRun" as the InvocationType. This is useful in a cross-account scenario when you want to verify access to a function without running it.'));
        $builder->add($elementFactory->newSelect('log_type', 'Log-Type', $logTypes, 'You can set this optional parameter to "Tail" in the request only if you specify the InvocationType parameter with value "RequestResponse". In this case, AWS Lambda returns the base64-encoded last 4 KB of log data produced by your Lambda function in the x-amz-log-results header.'));
        $builder->add($elementFactory->newInput('qualifier', 'Qualifier', 'text', 'You can use this optional paramter to specify a Lambda function version or alias name. If you specify function version, the API uses qualified function ARN to invoke a specific Lambda function. If you specify alias name, the API uses the alias ARN to invoke the Lambda function version to which the alias points.'));
        $builder->add($elementFactory->newTextArea('client_context', 'Client-Context', 'json', 'Using the ClientContext you can pass client-specific information to the Lambda function you are invoking. You can then process the client information in your Lambda function as you choose through the context variable.'));
    }
}
