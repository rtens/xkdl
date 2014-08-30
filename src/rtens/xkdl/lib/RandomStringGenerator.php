<?php
namespace rtens\xkdl\lib;

class RandomStringGenerator {

    public static $CLASS = __CLASS__;

    public function generate() {
        return md5(mt_rand() . microtime());
    }
}