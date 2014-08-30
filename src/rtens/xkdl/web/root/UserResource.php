<?php
namespace rtens\xkdl\web\root;

use rtens\xkdl\lib\AuthenticationService;
use rtens\xkdl\web\Session;
use watoki\curir\resource\DynamicResource;

class UserResource extends DynamicResource {

    /** @var Session <- */
    public $session;

    /** @var AuthenticationService <- */
    public $authentication;

    public function doPost($email) {
        $url = $this->getUrl();
        $url->getParameters()->set('method', 'login');
        $this->authentication->request($email, $url, 'otp');
    }

    public function doLogin($otp) {
        $this->session->setLoggedIn($this->authentication->authenticate($otp));
    }

    public function doLogout() {
        $this->session->requireLoggedIn($this);
        $this->authentication->logout($this->session->getUserId());
        $this->session->setLoggedIn(false);
        return "Logged out";
    }

} 