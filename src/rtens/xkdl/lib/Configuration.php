<?php
namespace rtens\xkdl\lib;

class Configuration {

    public static $CLASS = __CLASS__;

    private $root;

    function __construct($root) {
        $this->root = $root;
    }

    public function defaultDuration() {
        return new TimeSpan('PT15M');
    }

    public function rootTaskFolder() {
        return $this->userFolder() . '/root';
    }

    public function userFolder() {
        return $this->root . '/user';
    }

} 