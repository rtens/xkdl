<?php
namespace rtens\xkdl;

use DateTime;
use rtens\xkdl\lib\ExecutionWindow;
use rtens\xkdl\lib\RepeatedExecutionWindows;
use rtens\xkdl\lib\Slot;
use rtens\xkdl\lib\TimeSpan;
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

    /** @var int Priority relative to siblings */
    protected $priority = 9999;

    /** @var TimeSpan */
    protected $duration;

    /** @var array|TimeWindow[] */
    protected $logs = array();

    /** @var array|ExecutionWindow[] */
    protected $windows = array();

    /** @var array|Task[] */
    protected $dependencies = array();

    function __construct($name, TimeSpan $duration = null) {
        $this->name = $name;
        $this->duration = $duration ?: new TimeSpan('PT0S');
    }

    public function getParent() {
        return $this->parent;
    }

    public function getName() {
        return $this->name;
    }

    public function getFullName() {
        return ($this->parent ? ($this->parent->getFullName() . '/' . $this->getName()) : '');
    }

    private function getFullNameWithPriorities() {
        return ($this->parent ? ($this->parent->getFullNameWithPriorities() . '/'
            . sprintf('%04d', $this->priority) . $this->getName()) : '');
    }

    public function setDone($done = true) {
        $this->done = $done;
    }

    public function isDone() {
        return $this->done;
    }

    /**
     * @return null|\DateTime
     */
    public function getDeadline() {
        return $this->deadline ? : ($this->parent ? $this->parent->getDeadline() : null);
    }

    public function setDeadline(\DateTime $deadline) {
        $this->deadline = $deadline;
    }

    public function setPriority($priority) {
        $this->priority = $priority;
    }

    public function getPriority() {
        return $this->priority;
    }

    public function hasPriority() {
        return $this->priority != null;
    }

    public function setDuration(TimeSpan $duration = null) {
        $this->duration = $duration;
    }

    public function getDuration() {
        return $this->duration;
    }

    public function addChild(Task $child) {
        $child->parent = $this;
        $this->children[] = $child;
    }

    public function getChildren() {
        return $this->children;
    }

    public function addLog(TimeWindow $window) {
        $this->logs[] = $window;
    }

    public function getLogs() {
        return $this->logs;
    }

    public function addWindow(ExecutionWindow $window) {
        $this->windows[] = $window;
    }

    public function getWindows() {
        return $this->windows;
    }

    public function repeatWindow(\DateInterval $every) {
        $this->windows = new RepeatedExecutionWindows($every, $this->windows);
    }

    public function addDependency(Task $dependency) {
        $this->dependencies[] = $dependency;
    }

    /**
     * @param \DateTime $now
     * @param array|Slot[] $slots
     * @param DateTime $until
     * @return array|Task[]
     */
    public function getSchedulableTasks(\DateTime $now, array $slots, \DateTime $until) {
        if ($this->done || !$this->isInWindow($now, $slots, $until)) {
            return array();
        }

        $tasks = array();
        foreach ($this->children as $child) {
            foreach ($child->getSchedulableTasks($now, $slots, $until) as $task) {
                $tasks[] = $task;
            }
            if ($child->isSchedulable($now, $slots, $until)) {
                $tasks[] = $child;
            }
        }
        return $tasks;
    }

    /**
     * @param DateTime $now
     * @param array|Slot[] $slots
     * @param \DateTime $until
     * @return bool
     */
    protected function isSchedulable(\DateTime $now, array $slots, \DateTime $until) {
        return (!$this->done
            && $this->duration->seconds()
            && count($this->getOpenChildren()) == 0
            && $this->isInWindow($now, $slots, $until)
            && $this->areAllDependenciesScheduled($slots)
            && $this->hasUnscheduledDuration($slots));
    }

    /**
     * @param \DateTime $now
     * @param array|Slot[] $slots
     * @param $until
     * @return bool
     */
    private function isInWindow(\DateTime $now, $slots, $until) {
        if (empty($this->windows)) {
            return true;
        }

        foreach ($this->windows as $window) {
            if ($window->start > $until) {
                return false;
            }

            if ($window->start <= $now && $now < $window->end
                && $this->isScheduledTimeSmallerThanQuota($window, $slots)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param \rtens\xkdl\lib\ExecutionWindow $window
     * @param array|Slot[] $slots
     * @return bool
     */
    private function isScheduledTimeSmallerThanQuota(ExecutionWindow $window, $slots) {
        if (!$window->quota) {
            return true;
        }

        $secondsScheduledInWindow = 0;
        foreach ($slots as $slot) {
            if ($slot->task == $this && $window->start <= $slot->window->start && $slot->window->end <= $window->end) {
                $secondsScheduledInWindow += $slot->window->getSeconds();
            }
        }
        return $secondsScheduledInWindow < $window->quota * 3600;
    }

    /**
     * @param array|Slot[] $slots
     * @return bool
     */
    private function areAllDependenciesScheduled($slots) {
        foreach ($this->dependencies as $task) {
            if ($task->hasUnscheduledDuration($slots)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array|Slot[] $slots
     * @return float
     */
    private function hasUnscheduledDuration(array $slots) {
        $unscheduledSeconds = $this->duration->seconds() - $this->getLoggedDuration()->seconds();
        foreach ($slots as $slot) {
            if ($slot->task == $this) {
                $unscheduledSeconds -= $slot->window->getSeconds();
                if ($unscheduledSeconds <= 0) {
                    return false;
                }
            }
        }
        return true;
    }

    public function getLoggedDuration() {
        $durationSeconds = 0;
        foreach ($this->logs as $log) {
            $durationSeconds += $log->getSeconds();
        }
        return new TimeSpan('PT' . $durationSeconds . 'S');
    }

    /**
     * @param $name
     * @return Task
     * @throws \Exception
     */
    public function getChild($name) {
        foreach ($this->children as $child) {
            if ($child->name == $name) {
                return $child;
            }
        }
        throw new \Exception("Child [$name] not found in [{$this->name}]");
    }

    /**
     * @return array|Task[]
     */
    public function getOpenChildren() {
        $openChildren = array();
        foreach ($this->children as $child) {
            if (!$child->isDone()) {
                $openChildren[] = $child;
            }
        }
        return $openChildren;
    }

    public function hasHigherPriorityThen(Task $task) {
        return $this->getFullNameWithPriorities() < $task->getFullNameWithPriorities();
    }
}