<?php

use rtens\xkdl\lib\Configuration;
use rtens\xkdl\web\RootResource;
use rtens\xkdl\web\Session;
use watoki\cfg\Loader;
use watoki\curir\WebDelivery;
use watoki\factory\Factory;

require_once 'bootstrap.php';

$factory = new Factory();

$loader = new Loader($factory);

$userConfigFile = __DIR__ . '/user/UserConfiguration.php';
$loader->loadConfiguration(Configuration::$CLASS, $userConfigFile, [__DIR__]);

$session = new Session();
$factory->setSingleton(Session::$CLASS, $session);

WebDelivery::quickStart(RootResource::$CLASS, $factory);