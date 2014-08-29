<?php
namespace rtens\xkdl\scheduler;

use rtens\xkdl\Scheduler;
use rtens\xkdl\Task;
use watoki\factory\Factory;

class SchedulerFactory {

    public static $CLASS = __CLASS__;

    const KEY_EDF = 'edf';
    const KEY_PRIORITY = 'prio';

    /** @var Factory <- */
    public $factory;

    private $schedulers = [];

    public function __construct() {
        $this->set(self::KEY_EDF, EdfScheduler::$CLASS,
            'Earliest Deadline First', 'first by deadline, then by priority');

        $this->set(self::KEY_PRIORITY, PriorityScheduler::$CLASS,
            'Highest Priority First', 'first by priority, then by deadline');
    }

    public function clear() {
        $this->schedulers = [];
    }

    public function set($key, $schedulerClass, $name, $description) {
        $this->schedulers[$key] = [
            'class' => $schedulerClass,
            'name' => $name,
            'description' => $description
        ];
    }

    /**
     * @param string $key
     * @param \rtens\xkdl\Task $root
     * @throws \Exception If the $key does not exist
     * @return Scheduler
     */
    public function create($key, Task $root) {
        if (!array_key_exists($key, $this->schedulers)) {
            throw new \Exception('Invalid scheduler key: ' . $key);
        }
        return $this->factory->getInstance($this->schedulers[$key]['class'], [$root]);
    }

    public function all() {
        return $this->schedulers;
    }

} 