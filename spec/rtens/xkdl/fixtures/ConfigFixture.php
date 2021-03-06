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

    private $rootDir;

    public function setUp() {
        parent::setUp();

        $this->rootDir = __DIR__ . '/tmp';
        @mkdir($this->rootDir);
        $this->clear($this->rootDir);

        $mf = new MockFactory();
        $this->config = $mf->getInstance(Configuration::$CLASS, [$this->rootDir]);
        $this->config->__mock()->mockMethods(Mockster::F_NONE);

        $this->spec->factory->setSingleton(Configuration::$CLASS, $this->config);
        $this->spec->factory->setSingleton('watoki\cfg\Loader', $mf->getInstance('watoki\cfg\Loader'));
    }

    public function tearDown() {
        $this->clear($this->rootDir);
        @rmdir($this->rootDir);
    }

    private function clear($dir) {
        foreach (glob($dir . '/*') as $file) {
            if (is_file($file)) {
                @unlink($file);
            } else {
                $this->clear($file);
                @rmdir($file);
            }
        }
    }

    /**
     * @return Configuration
     */
    public function getConfig() {
        return $this->config;
    }

    public function givenTheDefaultDurationIs_Minutes($int) {
        $this->config->__mock()->method('defaultDuration')->willReturn(new TimeSpan('PT' . $int . 'M'));
        $this->config->__mock()->method('defaultDurationString')->willReturn('PT' . $int . 'M');
    }

    public function givenNowIs($when) {
        $this->config->__mock()->method('now')->willReturn(new \DateTime($when));
    }

    public function givenTheDefaultSchedulerIs($key) {
        $this->config->__mock()->method('defaultSchedulerKey')->willReturn($key);
    }
}