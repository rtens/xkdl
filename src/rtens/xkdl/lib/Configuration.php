<?php
namespace rtens\xkdl\lib;

class Configuration {

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

    public function scheduleArchiveFileName() {
        return $this->now()->format('Ymd\THis') . '.txt';
    }

    public function now() {
        return new \DateTime();
    }

} 