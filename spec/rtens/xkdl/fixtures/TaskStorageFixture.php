<?php
namespace spec\rtens\xkdl\fixtures;

use rtens\mockster\MockFactory;
use rtens\xkdl\lib\TimeSpan;
use rtens\xkdl\storage\TaskStore;
use rtens\xkdl\Task;
use watoki\scrut\Fixture;

class TaskStorageFixture extends Fixture {

    /** @var Task */
    public $root;

    /** @var Task[] */
    private $tasks = array();

    public function setUp() {
        parent::setUp();
        $this->root = new Task('root');
        $this->tasks['.'] = $this->root;

        $mf = new MockFactory();
        $store = $mf->getInstance(TaskStore::$CLASS);
        $store->__mock()->method('getRoot')->willReturn($this->root);

        $this->spec->factory->setSingleton(TaskStore::$CLASS, $store);
    }

    public function givenTheTask_OfType($fullName, $class) {
        $this->tasks[$fullName] = $this->spec->factory->getInstance($class,
            [basename($fullName), new TimeSpan('PT1M')]);
        $this->tasks[dirname($fullName)]->addChild($this->tasks[$fullName]);
    }

} 