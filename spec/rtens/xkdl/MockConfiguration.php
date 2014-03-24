<?php
namespace spec\rtens\xkdl;

use rtens\xkdl\lib\Configuration;

class MockConfiguration extends Configuration {
    public $defaultDuration;

    public function defaultDuration() {
        return $this->defaultDuration ? : parent::defaultDuration();
    }

    public function scheduleArchiveFileName() {
        return date('Y-m-d_H-i-s', strtotime('2001-01-01 10:10:10')) . '.txt';
    }

} 