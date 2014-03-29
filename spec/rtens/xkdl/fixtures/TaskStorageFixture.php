<?php
namespace spec\rtens\xkdl\fixtures;

use rtens\mockster\MockFactory;
use rtens\xkdl\lib\TimeSpan;
use rtens\xkdl\storage\Reader;
use rtens\xkdl\Task;
use watoki\scrut\Fixture;

class TaskStorageFixture extends Fixture {

    /** @var Task */
    public $root;

    /** @var Task[] */
    private $tasks = array();

    protected function setUp() {
        parent::setUp();
        $this->root = new Task('root');
        $this->tasks['.'] = $this->root;

        $mf = new MockFactory();
        $reader = $mf->getInstance(Reader::CLASS);
        $reader->__mock()->method('read')->willReturn($this->root);

        $this->spec->factory->setSingleton(Reader::CLASS, $reader);
    }

    public function givenTheTask_OfType($fullName, $class) {
        $this->tasks[$fullName] = $this->spec->factory->getInstance($class,
            [basename($fullName), new TimeSpan('PT1M')]);
        $this->tasks[dirname($fullName)]->addChild($this->tasks[$fullName]);
    }

} 