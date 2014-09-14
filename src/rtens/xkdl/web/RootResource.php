<?php
namespace rtens\xkdl\web;

use rtens\xkdl\exception\AuthenticationException;
use rtens\xkdl\exception\NotLoggedInException;
use rtens\xkdl\lib\AuthenticationService;
use rtens\xkdl\lib\Configuration;
use watoki\cfg\Loader;
use watoki\curir\Container;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\responder\Redirecter;
use watoki\deli\Request;

class RootResource extends Container {

    public static $CLASS = __CLASS__;

    /** @var \Google_Client <- */
    public $client;

    /** @var Session <- */
    public $session;

    /** @var AuthenticationService <- */
    public $authentication;

    /** @var Configuration <- */
    public $config;

    /** @var Loader <- */
    public $loader;

    /**
     * @param Request|WebRequest $request
     * @return WebResponse
     */
    public function respond(Request $request) {
        if ($this->session->has('token')) {
            $this->client->setAccessToken($this->session->get('token'));
        }

        if ($this->session->isLoggedIn()) {
            $homeConfigFile = $this->config->userFolder() . '/home/' . $this->session->getUserId() . '/HomeConfiguration.php';
            if (!file_exists($homeConfigFile)) {
                mkdir(dirname($homeConfigFile), 0777, true);
                file_put_contents($homeConfigFile,
                    '<?php namespace rtens\xkdl\lib; class HomeConfiguration extends UserConfiguration {}');
            }
            $this->loader->loadConfiguration(Configuration::$CLASS, $homeConfigFile,
                [$this->config->getRoot(), $this->session->getUserId()]);
        }

        try {
            return parent::respond($request);
        } catch (AuthenticationException $ae) {
            return $this->createResponse(Redirecter::fromString($ae->getAuthenticationUrl()), $request);
        } catch (NotLoggedInException $nlie) {
            return $this->createResponse(Redirecter::fromString('auth'), $request);
        }
    }

    public function doGet() {
        return Redirecter::fromString('schedule');
    }

    public function doAuthenticate($code) {
        $this->client->authenticate($code);
        $this->session->set('token', $this->client->getAccessToken());
        return Redirecter::fromString('');
    }

} 