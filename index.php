<?php
use rtens\xkdl\lib\Configuration;
use rtens\xkdl\web\RootResource;
use rtens\xkdl\web\Session;
use watoki\cfg\Loader;
use watoki\curir\WebApplication;
use watoki\factory\Factory;

require_once 'bootstrap.php';

$factory = new Factory();

$loader = new Loader($factory);

$userConfigFile = __DIR__ . '/user/UserConfiguration.php';
$loader->loadConfiguration(Configuration::$CLASS, $userConfigFile, [__DIR__]);

$session = new Session();
$factory->setSingleton(Session::$CLASS, $session);
if ($session->isLoggedIn()) {
    $homeConfigFile = __DIR__ . '/user/home/' . $session->getUserId() . '/HomeConfiguration.php';
    if (!file_exists($homeConfigFile)) {
        mkdir(dirname($homeConfigFile), 0777, true);
        file_put_contents($homeConfigFile,
            '<?php namespace rtens\xkdl\lib; class HomeConfiguration extends UserConfiguration {}');
    }
    $loader->loadConfiguration(Configuration::$CLASS, $homeConfigFile, [__DIR__, $session->getUserId()]);
}

WebApplication::quickStart(RootResource::$CLASS, $factory);