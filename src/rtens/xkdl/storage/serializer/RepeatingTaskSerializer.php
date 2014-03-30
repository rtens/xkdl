<?php
namespace rtens\xkdl\storage\serializer;

use rtens\xkdl\lib\TimeSpan;
use rtens\xkdl\Task;
use rtens\xkdl\task\RepeatingTask;

class RepeatingTaskSerializer extends TaskSerializer {

    protected function createInstance($name, TimeSpan $duration = null) {
        return new RepeatingTask($name, $duration);
    }

    /**
     * @param Task|RepeatingTask $task
     * @param $name
     * @param $value
     */
    protected function setProperty(Task $task, $name, $value) {
        switch ($name) {
            case 'repeat':
                $task->repeatEvery(new \DateInterval($value));
                break;
            default:
                parent::setProperty($task, $name, $value);
        }
    }

} 