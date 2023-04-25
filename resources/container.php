<?php

use Fusio\Adapter\Aws\Action\AwsLambdaInvoke;
use Fusio\Adapter\Aws\Connection\Aws;
use Fusio\Adapter\Aws\Generator\AwsLambda;
use Fusio\Engine\Adapter\ServiceBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $services = ServiceBuilder::build($container);
    $services->set(Aws::class);
    $services->set(AwsLambdaInvoke::class);
    $services->set(AwsLambda::class);
};
