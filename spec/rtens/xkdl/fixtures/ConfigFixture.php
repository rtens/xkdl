<?php
namespace spec\rtens\xkdl\fixtures;

use rtens\mockster\Mock;
use rtens\mockster\MockFactory;
use rtens\mockster\Mockster;
use rtens\xkdl\lib\Configuration;
use rtens\xkdl\lib\TimeSpan;
use watoki\scrut\Fixture;

class ConfigFixture extends Fixture {

    /** @var Mock */
    private $config;

    protected function setUp() {
        parent::setUp();

        $root = $this->tmpDir();

        $mf = new MockFactory();
        $this->config = $mf->getInstance(Configuration::CLASS, [$root]);
        $this->config->__mock()->mockMethods(Mockster::F_NONE);

        @mkdir($root);
        $this->spec->undos[] = function () use ($root) {
            @rmdir($root);
        };
    }

    /**
     * @return Configuration
     */
    public function getConfig() {
        return $this->config;
    }

    public function givenTheDefaultDurationIs_Minutes($int) {
        $this->config->__mock()->method('defaultDuration')->willReturn(new TimeSpan('PT' . $int . 'M'));
    }

    public function givenNowIs($when) {
        $this->config->__mock()->method('now')->willReturn(new \DateTime($when));
    }

    public function tmpDir() {
        return __DIR__ . '/tmp';
    }
}