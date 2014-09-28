<?php
namespace rtens\xkdl\lib\auth;

class Hasher {

    public function hash($a, $b) {
        return md5($a . $b);
    }

} 