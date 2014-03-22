<?php
namespace spec\rtens\xkdl;

use rtens\xkdl\lib\Configuration;

class MockConfiguration extends Configuration {
    public $defaultDuration;

    public function defaultDuration() {
        return $this->defaultDuration ? : parent::defaultDuration();
    }

} 