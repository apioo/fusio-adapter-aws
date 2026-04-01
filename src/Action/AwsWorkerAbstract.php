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
use Fusio\Engine\Action\LifecycleInterface;
use Fusio\Engine\Action\RuntimeInterface;
use Fusio\Engine\ActionAbstract;
use Fusio\Engine\Connection\PingableInterface;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use Fusio\Engine\Worker\ExecuteBuilderInterface;
use Fusio\Worker\Client;
use PSX\Json\Parser;

/**
 * AwsWorkerAbstract
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org
 */
abstract class AwsWorkerAbstract extends ActionAbstract implements LifecycleInterface, PingableInterface
{
    private ExecuteBuilderInterface $executeBuilder;

    public function __construct(RuntimeInterface $runtime, ExecuteBuilderInterface $executeBuilder)
    {
        parent::__construct($runtime);

        $this->executeBuilder = $executeBuilder;
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): mixed
    {
        $sdk = $this->connector->getConnection($configuration->get('connection'));
        if (!$sdk instanceof Sdk) {
            throw new ConfigurationException('Provided an invalid worker connection');
        }

        $args = [
            'FunctionName' => $context->getAction()?->getName(),
            'InvocationType' => 'RequestResponse',
            'LogType' => 'None',
            'Payload' => Parser::encode($this->executeBuilder->build($request, $context)),
            'ClientContext' => base64_encode(Parser::encode($context)),
        ];

        $result = $sdk->createLambda()->invoke($args);

        $payload = Parser::decodeAsObject($result->get('Payload') ?? '{}');

        $events = $payload->events ?? null;
        if (is_array($events)) {
            foreach ($events as $event) {
                $eventName = $event->eventName ?? null;
                $data = $event->data ?? null;
                if ($eventName !== null && $data !== null) {
                    $this->dispatcher->dispatch($eventName, $data);
                }
            }
        }


        $logs = $payload->logs ?? null;
        if (is_array($logs)) {
            foreach ($logs as $log) {
                $level = $log->level ?? null;
                $message = $log->message ?? null;
                if ($level !== null && $message !== null) {
                    $this->logger->log($level, $message);
                }
            }
        }

        $httpResponse = $payload->response ?? null;

        return $this->response->build(
            $httpResponse?->statusCode ?? 200,
            $httpResponse?->headers ?? [],
            $httpResponse?->body
        );
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The AWS connection'));
        $builder->add($elementFactory->newInput('memory', 'Memory-Size', 'number', 'The memory size of this lambda function, default is 128mb'));
        $builder->add($elementFactory->newInput('role', 'Role', 'text', 'The IAM role to execute this lambda function, default is lambda_basic_execution'));
        $builder->add($elementFactory->newTextArea('code', 'Code', $this->getLanguage(), ''));
    }

    public function onCreate(string $name, ParametersInterface $config): void
    {
        $sdk = $this->connector->getConnection($config->get('connection'));
        if (!$sdk instanceof Sdk) {
            return;
        }

        $code = $config->get('code');
        if (empty($code)) {
            throw new ConfigurationException('Provided no code');
        }

        $memorySize = $config->get('memory');
        if (empty($memorySize)) {
            $memorySize = 128;
        }

        $role = $config->get('role');
        if (empty($role)) {
            $role = 'lambda_basic_execution';
        }

        $sdk->createLambda()->createFunction([
            'Code' => [
                'ZipFile' => $this->createZipFile($name, $code),
            ],
            'FunctionName' => 'index',
            'Handler' => 'handler',
            'MemorySize' => $memorySize,
            'PackageType' => 'zip',
            'Publish' => true,
            'Role' => $role,
            'Runtime' => $this->getRuntime(),
            'Layers' => ['fusio-worker-' . $this->getRuntime()],
        ]);
    }

    public function onUpdate(string $name, ParametersInterface $config): void
    {
        $sdk = $this->connector->getConnection($config->get('connection'));
        if (!$sdk instanceof Sdk) {
            return;
        }

        $code = $config->get('code');
        if (empty($code)) {
            throw new ConfigurationException('Provided no code');
        }

        $sdk->createLambda()->updateFunctionCode([
            'ZipFile' => $this->createZipFile($name, $code),
        ]);
    }

    public function onDelete(string $name, ParametersInterface $config): void
    {
        $sdk = $this->connector->getConnection($config->get('connection'));
        if (!$sdk instanceof Sdk) {
            return;
        }

        $sdk->createLambda()->deleteFunction([
            'Name' => $name,
        ]);
    }

    public function ping(mixed $connection): bool
    {
        if (!$connection instanceof Client) {
            return false;
        }

        $apiVersion = $connection->get()->getApiVersion();
        if ($apiVersion === null) {
            return false;
        }

        return true;
    }

    abstract protected function getLanguage(): string;
    abstract protected function getRuntime(): string;

    private function createZipFile(string $name, string $code): string
    {
        $zipFile = sys_get_temp_dir() . '/fusio-worker-' . $this->getLanguage() . '-' . $name . '.zip';
        if (is_file($zipFile)) {
            unlink($zipFile);
        }

        $zip = new \ZipArchive();
        $zip->open($zipFile, \ZipArchive::CREATE);
        $zip->addFromString('action.js', $code);
        $zip->close();

        return $zipFile;
    }
}
