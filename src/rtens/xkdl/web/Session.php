<?php
namespace rtens\xkdl\web;

use rtens\xkdl\exception\NotLoggedInException;
use watoki\curir\delivery\WebRequest;

class Session {

    public static $CLASS = __CLASS__;

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
        return isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'];
    }

    public function getUserId() {
        return $_SESSION['loggedIn'];
    }

    public function setLoggedIn($as) {
        $_SESSION['loggedIn'] = strtolower($as);
    }

    public function requireLoggedIn(WebRequest $request) {
        if (!$this->isLoggedIn()) {
            throw new NotLoggedInException($request->getContext());
        }
    }

}