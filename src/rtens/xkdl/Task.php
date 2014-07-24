<?php
namespace rtens\xkdl;

use DateTime;
use rtens\xkdl\lib\ExecutionWindow;
use rtens\xkdl\lib\RepeatedExecutionWindows;
use rtens\xkdl\lib\TimeSpan;
use rtens\xkdl\lib\TimeWindow;

class Task {

    public static $CLASS = __CLASS__;

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

    public function getDependencies() {
        return $this->dependencies;
    }

    /**
     * @param \DateTime $now
     * @internal param array|\rtens\xkdl\lib\Slot[] $slots
     * @return array|Task[]
     */
    public function getSchedulableTasks(\DateTime $now) {
        if ($this->done || !$this->isInWindow($now)) {
            return array();
        }

        $tasks = array();
        foreach ($this->getChildren() as $child) {
            foreach ($child->getSchedulableTasks($now) as $task) {
                $tasks[] = $task;
            }
            if ($child->isSchedulable($now)) {
                $tasks[] = $child;
            }
        }
        return $tasks;
    }

    /**
     * @param DateTime $now
     * @internal param array|\rtens\xkdl\lib\Slot[] $slots
     * @return bool
     */
    protected function isSchedulable(\DateTime $now) {
        return (!$this->done
            && $this->duration->seconds()
            && count($this->getOpenChildren()) == 0
            && $this->isInWindow($now));
    }

    /**
     * @param \DateTime $now
     * @internal param array|\rtens\xkdl\lib\Slot[] $slots
     * @return bool
     */
    private function isInWindow(\DateTime $now) {
        if (empty($this->windows)) {
            return true;
        }

        foreach ($this->windows as $window) {
            if ($window->start > $now) {
                return false;
            }

            if ($now < $window->end) {
                return true;
            }
        }
        return false;
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
        foreach ($this->getChildren() as $child) {
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
        foreach ($this->getChildren() as $child) {
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