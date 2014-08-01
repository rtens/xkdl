<?php
namespace rtens\xkdl\web;

use rtens\xkdl\exception\NotLoggedInException;
use rtens\xkdl\lib\Configuration;

class Session {

    public static $CLASS = __CLASS__;

    /** @var Configuration <- */
    public $config;

    public function __construct() {
        session_start();
    }

    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public function get($key) {
        return $_SESSION[$key];
    }

    public function has($key) {
        return array_key_exists($key, $_SESSION);
    }

    public function isLoggedIn() {
        return $_SESSION['loggedIn'];
    }

    public function setLoggedIn($to = true) {
        $_SESSION['loggedIn'] = $to;
    }

    public function requireLoggedIn() {
        if (!$this->isLoggedIn()) {
            throw new NotLoggedInException();
        }
    }

}