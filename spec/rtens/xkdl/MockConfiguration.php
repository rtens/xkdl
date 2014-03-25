<?php
namespace spec\rtens\xkdl;

use rtens\xkdl\lib\Configuration;

class MockConfiguration extends Configuration {
    public $defaultDuration;

    public function defaultDuration() {
        return $this->defaultDuration ? : parent::defaultDuration();
    }

    public function scheduleArchiveFileName(\DateTime $date = null) {
        return parent::scheduleArchiveFileName(new \DateTime('2001-01-01 10:10:10'));
    }

} 