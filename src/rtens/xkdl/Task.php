<?php
namespace rtens\xkdl;

use DateTime;
use rtens\xkdl\lib\TimeWindow;

class Task {

    /** @var string */
    protected $name;

    /** @var bool */
    protected $done = false;

    /** @var Task|null */
    protected $parent;

    /** @var array|Task[] */
    protected $children = array();

    /** @var \DateTime|null */
    protected $deadline;

    /** @var float [hours] */
    protected $duration;

    /** @var array|TimeWindow[] */
    protected $logs = array();

    /** @var array|TimeWindow[] */
    protected $windows = array();

    /** @var array|Task[] */
    protected $dependencies = array();

    function __construct($name, $duration) {
        $this->name = $name;
        $this->duration = $duration;
    }

    public function getName() {
        return $this->name;
    }

    public function setDone($done = true) {
        $this->done = $done;
    }

    public function getDeadline() {
        return $this->deadline ? : ($this->parent ? $this->parent->getDeadline() : null);
    }

    public function setDeadline(\DateTime $deadline) {
        $this->deadline = $deadline;
    }

    public function setDuration($duration) {
        $this->duration = $duration;
    }

    public function addChild(Task $child) {
        $child->parent = $this;
        $this->children[] = $child;
    }

    public function addLog(TimeWindow $window) {
        $this->logs[] = $window;
    }

    public function addWindow(TimeWindow $window) {
        $this->windows[] = $window;
    }

    public function addDependency(Task $dependency) {
        $this->dependencies[] = $dependency;
    }

    /**
     * @param \DateTime $now
     * @param array|Slot[] $schedule
     * @param DateTime $until
     * @return array|Task[]
     */
    public function getSchedulableTasks(\DateTime $now, array $schedule, \DateTime $until) {
        if ($this->done || !$this->isInWindow($now, $schedule)) {
            return array();
        }

        $tasks = array();
        foreach ($this->children as $child) {
            foreach ($child->getSchedulableTasks($now, $schedule, $until) as $task) {
                $tasks[] = $task;
            }
            if ($child->isSchedulable($now, $schedule)) {
                $tasks[] = $child;
            }
        }
        return $tasks;
    }

    /**
     * @param DateTime $now
     * @param array|Slot[] $schedule
     * @return bool
     */
    protected function isSchedulable(\DateTime $now, array $schedule) {
        return (!$this->done
            && empty($this->children)
            && $this->isInWindow($now, $schedule)
            && $this->areAllDependenciesScheduled($schedule)
            && $this->hasUnscheduledDuration($schedule));
    }

    /**
     * @param \DateTime $now
     * @param array|Slot[] $schedule
     * @return bool
     */
    private function isInWindow(\DateTime $now, $schedule) {
        if (empty($this->windows)) {
            return true;
        }

        foreach ($this->windows as $window) {
            if ($window->start <= $now && $now < $window->end
                && $this->isScheduledTimeSmallerThanQuota($window, $schedule)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param \rtens\xkdl\lib\TimeWindow $window
     * @param array|Slot[] $schedule
     * @return bool
     */
    private function isScheduledTimeSmallerThanQuota(TimeWindow $window, $schedule) {
        if (!$window->quota) {
            return true;
        }

        $secondsScheduledInWindow = 0;
        foreach ($schedule as $slot) {
            if ($slot->task == $this && $window->start <= $slot->window->start && $slot->window->end <= $window->end) {
                $secondsScheduledInWindow += $slot->window->getSeconds();
            }
        }
        return $secondsScheduledInWindow < $window->quota * 3600;
    }

    /**
     * @param array|Slot[] $schedule
     * @return bool
     */
    private function areAllDependenciesScheduled($schedule) {
        foreach ($this->dependencies as $task) {
            if ($task->hasUnscheduledDuration($schedule)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array|Slot[] $schedule
     * @return float
     */
    private function hasUnscheduledDuration(array $schedule) {
        $unscheduledSeconds = $this->getUnloggedDuration() * 3600;
        foreach ($schedule as $slot) {
            if ($slot->task == $this) {
                $unscheduledSeconds -= $slot->window->getSeconds();
            }
            if ($unscheduledSeconds <= 0) {
                return false;
            }
        }
        return $unscheduledSeconds > 0;
    }

    private function getUnloggedDuration() {
        $seconds = $this->duration * 3600;
        foreach ($this->logs as $log) {
            $seconds -= $log->getSeconds();
        }
        return $seconds / 3600;
    }
}