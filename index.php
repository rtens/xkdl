<?php
use rtens\xkdl\lib\Configuration;
use rtens\xkdl\web\RootResource;
use watoki\cfg\Loader;
use watoki\curir\WebApplication;
use watoki\factory\Factory;

require_once 'bootstrap.php';

$factory = new Factory();

$loader = new Loader($factory);
$loader->loadConfiguration(Configuration::$CLASS, __DIR__ . '/user/UserConfiguration.php', array(__DIR__));

WebApplication::quickStart(RootResource::$CLASS, $factory);