<?php
namespace rtens\xkdl\task;

use rtens\xkdl\lib\ExecutionWindow;
use rtens\xkdl\Task;

class RepeatingTask extends Task {

    public static $CLASS = __CLASS__;

    /** @var \DateInterval */
    private $repetition;

    public function repeatWindow(\DateInterval $every) {
        throw new \Exception('A repeating task cannot have repeating windows');
    }

    public function repeatEvery(\DateInterval $interval) {
        $this->repetition = $interval;
    }

    public function getRepetition() {
        return $this->repetition;
    }

    protected function isSchedulable(\DateTime $now) {
        return false;
    }

    public function getSchedulableTasks(\DateTime $now) {
        for ($i = 0; true; $i++) {
            $task = $this->generateRepetition($i);
            if (!$this->hasWindowBefore($task, $now)) {
                return array();
            }
            if ($task->isSchedulable($now)) {
                return array($task);
            }
        }
        return array();
    }

    /**
     * @param $i
     * @return Task
     */
    private function generateRepetition($i) {
        $task = new Task($this->name . ' (' . ($i + 1) . ')', $this->duration);
        $task->parent = $this;
        $task->dependencies = $this->dependencies;

        if ($this->deadline) {
            $task->deadline = clone $this->deadline;
            for ($n = 0; $n < $i; $n++) {
                $task->deadline->add($this->repetition);
            }
        }

        foreach ($this->windows as $window) {
            $task->addWindow(new ExecutionWindow(clone $window->start, clone $window->end, $window->quota));
        }

        for ($n = 0; $n < $i; $n++) {
            foreach ($task->windows as $window) {
                $window->start->add($this->repetition);
                $window->end->add($this->repetition);
            }
        }

        return $task;
    }

    private function hasWindowBefore(Task $task, \DateTime $now) {
        foreach ($task->windows as $window) {
            if ($window->start <= $now) {
                return true;
            }
        }
        return false;
    }

}