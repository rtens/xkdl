<?php
namespace rtens\xkdl\web;

use watoki\collections\Map;

class Session extends Map {

    public static $CLASS = __CLASS__;

    public function isLoggedIn() {
        return false;
    }

} 