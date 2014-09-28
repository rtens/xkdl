<?php

use rtens\xkdl\lib\auth\SessionStore;
use rtens\xkdl\lib\Configuration;
use rtens\xkdl\web\RootResource;
use watoki\cfg\Loader;
use watoki\curir\WebDelivery;
use watoki\factory\Factory;

require_once 'bootstrap.php';

$factory = new Factory();

$loader = new Loader($factory);

$userConfigFile = __DIR__ . '/user/UserConfiguration.php';
$loader->loadConfiguration(Configuration::$CLASS, $userConfigFile, [__DIR__]);

$factory->getSingleton(SessionStore::$CLASS, ['root' => __DIR__ . '/user/sessions']);

WebDelivery::quickStart(RootResource::$CLASS, WebDelivery::init($factory));