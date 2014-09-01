<?php
namespace rtens\xkdl\web;

use rtens\xkdl\exception\AuthenticationException;
use rtens\xkdl\exception\NotLoggedInException;
use rtens\xkdl\lib\AuthenticationService;
use rtens\xkdl\lib\Configuration;
use watoki\cfg\Loader;
use watoki\curir\http\error\HttpError;
use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\http\Url;
use watoki\curir\resource\Container;
use watoki\curir\responder\Redirecter;

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

    public function respond(Request $request) {
        if ($this->session->has('token')) {
            $this->client->setAccessToken($this->session->get('token'));
        }

        if (!$this->session->isLoggedIn() && $request->getCookies()->has('response')) {
            try {
                list($userId, $token) = $this->authentication->validateResponse($request->getCookies()->get('response'));
                $challenge = $this->authentication->createChallenge($userId, $token);
                $this->session->setLoggedIn($userId);
            } catch (\Exception $e) {
                throw new HttpError(Response::STATUS_UNAUTHORIZED, $e->getMessage());
            }
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
            $response = parent::respond($request);
            if (isset($challenge)) {
                $response->getHeaders()->set('X-Challenge', $challenge);
            }
            return $response;
        } catch (AuthenticationException $ae) {
            return (new Redirecter(Url::parse($ae->getAuthenticationUrl())))
                ->createResponse($request);
        } catch (NotLoggedInException $nlie) {
            return (new Redirecter($this->getUrl('auth')))->createResponse($request);
        }
    }

    public function doGet() {
        return new Redirecter($this->getUrl('schedule'));
    }

    public function doAuthenticate($code) {
        $this->client->authenticate($code);
        $this->session->set('token', $this->client->getAccessToken());
        return new Redirecter($this->getUrl());
    }

} 