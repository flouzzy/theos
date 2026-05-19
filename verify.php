<?php
require 'vendor/autoload.php';
$ref = new ReflectionClass(Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator::class);
var_dump($ref->hasMethod('nullOnInvalid'));
