<?php
namespace rtens\xkdl;

use rtens\xkdl\lib\TimeWindow;

class RepeatingTask extends Task {

    /** @var \DateInterval */
    private $repetition;

    public function repeatEvery(\DateInterval $interval) {
        $this->repetition = $interval;
    }

    public function getRepetition() {
        return $this->repetition;
    }

    protected function isSchedulable(\DateTime $now, array $schedule) {
        return false;
    }

    public function getSchedulableTasks(\DateTime $now, array $schedule, \DateTime $until) {
        for ($i = 0; true; $i++) {
            $task = $this->generateRepetition($i);
            if (!$this->hasWindowsContaining($task, $until)) {
                return array();
            }
            if ($task->isSchedulable($now, $schedule)) {
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
            $task->addWindow(new TimeWindow(clone $window->start, clone $window->end));
        }

        for ($n = 0; $n < $i; $n++) {
            foreach ($task->windows as $window) {
                $window->start->add($this->repetition);
                $window->end->add($this->repetition);
            }
        }

        return $task;
    }

    private function hasWindowsContaining(Task $task, \DateTime $until) {
        foreach ($task->windows as $window) {
            if ($window->start < $until) {
                return true;
            }
        }
        return false;
    }

}