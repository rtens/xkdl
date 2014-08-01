<?php
namespace rtens\xkdl\web\root;

use rtens\xkdl\lib\Configuration;
use rtens\xkdl\lib\OpenIdAuthenticator;
use rtens\xkdl\web\RootResource;
use rtens\xkdl\web\Session;
use watoki\curir\http\error\HttpError;
use watoki\curir\http\Response;
use watoki\curir\resource\DynamicResource;
use watoki\curir\responder\Redirecter;

class UserResource extends DynamicResource {

    /** @var OpenIdAuthenticator <- */
    public $authenticator;

    /** @var Session <- */
    public $session;

    /** @var Configuration <- */
    public $config;

    public function doLogin() {
        if (!$this->authenticator->isAuthenticated()) {
            throw new HttpError(Response::STATUS_UNAUTHORIZED);
        }

        $this->session->setLoggedIn();
        return new Redirecter($this->getAncestor(RootResource::$CLASS)->getUrl('schedule'));
    }

    public function doLogout() {
        $this->session->requireLoggedIn();
        $this->session->setLoggedIn(false);
    }

} 