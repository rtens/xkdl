<?php
namespace rtens\xkdl\lib;

abstract class Configuration {

    public static $CLASS = __CLASS__;

    private $root;

    function __construct($root) {
        $this->root = $root;
    }

    public function defaultDuration() {
        return new TimeSpan($this->defaultDurationString());
    }

    public function defaultDurationString() {
        return 'PT15M';
    }

    public function rootTaskFolder() {
        return $this->userFolder() . '/root';
    }

    public function userFolder() {
        return $this->root . '/user';
    }

    public function sessionFile() {
        return $this->userFolder() . '/session';
    }

    public function scheduleArchiveFileName() {
        return $this->now()->format('Ymd\THis') . '.txt';
    }

    public function now() {
        return new \DateTime();
    }

    /**
     * @return string
     */
    public function getHost() {
        return 'localhost';
    }

    /**
     * @return array|string[] Array of accepted openID identifiers
     */
    abstract public function getOpenIds();

} 