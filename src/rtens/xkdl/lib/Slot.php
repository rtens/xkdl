<?php
namespace rtens\xkdl\lib;

use rtens\xkdl\Task;

class Slot {

    /** @var Task */
    public $task;

    /** @var TimeWindow */
    public $window;

    public function __construct(Task $task, TimeWindow $window) {
        $this->task = $task;
        $this->window = $window;
    }
}