<?php
namespace rtens\xkdl\lib;

class EmailService {

    public static $CLASS = __CLASS__;

    public function send($to, $from, $subject, $body) {
        var_dump($to, $subject, $body, 'From: ' . $from);
    }
}