<?php
namespace rtens\xkdl\web\root;

use rtens\xkdl\lib\AuthenticationService;
use rtens\xkdl\web\Presenter;
use rtens\xkdl\web\RootResource;
use rtens\xkdl\web\Session;
use watoki\curir\resource\DynamicResource;
use watoki\curir\responder\Redirecter;

class UserResource extends DynamicResource {

    /** @var Session <- */
    public $session;

    /** @var AuthenticationService <- */
    public $authentication;

    public function doGet() {
        return new Presenter($this, ['sent' => false]);
    }

    public function doPost($email) {
        $url = $this->getUrl();
        $url->getParameters()->set('method', 'login');
        $this->authentication->request($email, $url, 'otp');

        return new Presenter($this, ['sent' => ['to' => $email], 'email' => false]);
    }

    public function doLogin($otp) {
        $this->session->setLoggedIn($this->authentication->authenticate($otp));
        return new Redirecter($this->getAncestor(RootResource::$CLASS)->getUrl());
    }

    public function doLogout() {
        $this->session->requireLoggedIn($this);
        $this->authentication->logout($this->session->getUserId());
        $this->session->setLoggedIn(false);

        return new Presenter($this, ['sent' => false]);
    }

} 