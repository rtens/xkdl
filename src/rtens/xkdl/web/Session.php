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
        return file_exists($this->config->sessionFile())
        && $this->has('session_token')
        && file_get_contents($this->config->sessionFile()) == $this->get('session_token');
    }

    public function setLoggedIn($to = true) {
        if ($to) {
            $token = md5(mt_rand());
            file_put_contents($this->config->sessionFile(), $token);
            $this->set('session_token', $token);
        } else {
            @unlink($this->config->sessionFile());
        }
    }

    public function requireLoggedIn() {
        if (!$this->isLoggedIn()) {
            throw new NotLoggedInException();
        }
    }

}