<?php
namespace rtens\xkdl\web\root;

use rtens\xkdl\lib\AuthenticationService;
use rtens\xkdl\lib\Configuration;
use rtens\xkdl\lib\EmailService;
use rtens\xkdl\web\Presenter;
use rtens\xkdl\web\RootResource;
use rtens\xkdl\web\Session;
use watoki\curir\http\error\HttpError;
use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\resource\DynamicResource;
use watoki\curir\responder\Redirecter;
use watoki\curir\Responder;

class AuthResource extends DynamicResource {

    /** @var Session <- */
    public $session;

    /** @var AuthenticationService <- */
    public $authentication;

    /** @var EmailService <- */
    public $email;

    /** @var Configuration <- */
    public $config;

    public function respond(Request $request) {
        $response = parent::respond($request);

        if ($this->session->isLoggedIn()) {
            $expire = $this->config->then(AuthenticationService::DEFAULT_EXPIRATION);
            $response->setCookie('token',
                $this->authentication->createToken($this->session->getUserId(), $expire),
                $expire->getTimestamp());
        }

        return $response;
    }

    public function doGet() {
        return new Presenter($this, ['sent' => false]);
    }

    public function doPost($email) {
        $token = $this->authentication->createToken($email, $this->config->then('5 minutes'));
        $this->sendEmail($email, $token);

        return new Presenter($this, ['sent' => ['to' => $email], 'email' => false]);
    }

    private function sendEmail($email, $token) {
        $url = $this->getUrl();
        $url->getParameters()->set('method', 'login');
        $url->getParameters()->set('token', $token);

        $this->email->send($email, 'xkdl@rtens.org', 'xkdl login', $url->toString());
    }

    public function doLogin($token) {
        try {
            $this->session->setLoggedIn($this->authentication->validateToken($token));
        } catch (\Exception $e) {
            throw new HttpError(Response::STATUS_UNAUTHORIZED, $e->getMessage());
        }
        return new Redirecter($this->getAncestor(RootResource::$CLASS)->getUrl());
    }

    public function doLogout() {
        $this->session->requireLoggedIn($this);
        $this->authentication->logout($this->session->getUserId());
        $this->session->setLoggedIn(false);

        return new Presenter($this, ['sent' => false]);
    }

} 