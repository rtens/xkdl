<?php
namespace rtens\xkdl\web\root;

use rtens\xkdl\lib\AuthenticationService;
use rtens\xkdl\lib\Configuration;
use rtens\xkdl\lib\EmailService;
use rtens\xkdl\web\Presenter;
use rtens\xkdl\web\Session;
use watoki\curir\http\Request;
use watoki\curir\resource\DynamicResource;
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
        return $response;
    }

    public function doGet() {
        return new Presenter($this, ['sent' => false]);
    }

    public function doPost($email) {
        $token = $this->authentication->createToken($email);
        $this->sendEmail($email, $token);

        $expire = $this->config->then('5 minutes');
        $challenge = $this->authentication->createChallenge($email, $token, $expire);

        return new Presenter($this, ['sent' => ['to' => $email], 'email' => false], ['X-Challenge' => $challenge]);
    }

    private function sendEmail($email, $token) {
        $url = $this->getUrl();
        $url->setFragment($token);

        $this->email->send($email, 'xkdl@rtens.org', 'xkdl login', $url->toString());
    }

    public function doLogout() {
        $this->session->requireLoggedIn($this);
        $this->authentication->logout($this->session->getUserId());
        $this->session->setLoggedIn(false);

        return new Presenter($this, ['sent' => false]);
    }

} 