<?php
namespace rtens\xkdl\web\root;

use rtens\xkdl\web\Session;
use watoki\curir\resource\DynamicResource;

class UserResource extends DynamicResource {

    /** @var Session <- */
    public $session;

    public function doLogout() {
        $this->session->requireLoggedIn($this);
        $this->session->setLoggedIn(false);
        return "Logged out";
    }

} 