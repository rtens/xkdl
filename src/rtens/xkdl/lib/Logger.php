<?php
namespace rtens\xkdl\lib;

class Logger {

    public static $CLASS = __CLASS__;

    /** @var Configuration <- */
    public $config;

    /** @var array */
    private $additionalData = [];

    public function log($object, $message) {
        $file = $this->config->userFolder() . '/logs/' . str_replace('\\', '_', get_class($object)) . '.csv';
        if (!file_exists(dirname($file))) {
            mkdir(dirname($file));
        }

        $line = array_merge([$this->config->now()->format('c')], $this->additionalData, [$message]);
        file_put_contents($file, implode(';', $line) . "\n", FILE_APPEND);
    }

    public function addData($data) {
        $this->additionalData[] = $data;
    }
}