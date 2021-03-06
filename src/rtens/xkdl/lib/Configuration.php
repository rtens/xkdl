<?php
namespace rtens\xkdl\lib;

use rtens\xkdl\scheduler\SchedulerFactory;
use watoki\curir\protocol\Url;

abstract class Configuration {

    public static $CLASS = __CLASS__;

    private $root;

    private $username;

    function __construct($root, $username = 'default') {
        $this->root = $root;
        $this->username = $username;
    }

    public function getRoot() {
        return $this->root;
    }

    public function defaultDuration() {
        return new TimeSpan($this->defaultDurationString());
    }

    public function defaultDurationString() {
        return 'PT15M';
    }

    public function defaultSchedulerKey() {
        return SchedulerFactory::KEY_EDF;
    }

    public function rootTaskFolder() {
        return $this->homeFolder() . '/root';
    }

    public function userFolder() {
        return $this->root . '/user';
    }

    public function homeFolder() {
        return $this->userFolder() . '/home/' . $this->userName();
    }

    protected function userName() {
        return $this->username;
    }

    public function scheduleArchiveFileName() {
        return $this->now()->format('Ymd\THis') . '.txt';
    }

    public function now() {
        return new \DateTime();
    }

    public function then($when) {
        $time = new \DateTime();
        $time->setTimestamp(strtotime($when, $this->now()->getTimestamp()));
        return $time;
    }

    public function getRootUrl() {
        return 'http://localhost';
    }

    public function getHost() {
        return Url::fromString($this->getRootUrl())->getHost();
    }

} 