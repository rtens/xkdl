<?php
namespace rtens\xkdl\web\root;

use rtens\xkdl\lib\OpenIdAuthenticator;
use rtens\xkdl\web\Session;
use watoki\curir\http\error\HttpError;
use watoki\curir\http\Response;
use watoki\curir\resource\DynamicResource;

class UserResource extends DynamicResource {

    /** @var OpenIdAuthenticator <- */
    public $authenticator;

    /** @var Session <- */
    public $session;

    public function doLogin() {
        if (!$this->authenticator->isAuthenticated()) {
            throw new HttpError(Response::STATUS_UNAUTHORIZED);
        }

        $this->session->setLoggedIn();
    }

} 