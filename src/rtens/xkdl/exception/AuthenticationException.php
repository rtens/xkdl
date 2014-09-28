<?php
namespace rtens\xkdl\exception;

use Exception;

class AuthenticationException extends \Exception {

    public static $CLASS = __CLASS__;

    private $authenticationUrl;

    public function __construct($authenticationUrl) {
        parent::__construct('Authentication required');
        $this->authenticationUrl = $authenticationUrl;
    }

    public function getAuthenticationUrl() {
        return $this->authenticationUrl;
    }

} 