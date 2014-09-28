<?php
namespace rtens\xkdl\lib\auth;

use Exception;

class InvalidSessionException extends \Exception {

    public function __construct($message = "", $code = 0, Exception $previous = null) {
        parent::__construct("Session validation failed", $code, $previous);
    }

}