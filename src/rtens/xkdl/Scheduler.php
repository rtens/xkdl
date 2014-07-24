<?php
namespace rtens\xkdl;

use rtens\xkdl\lib\Schedule;

abstract class Scheduler {

    protected $root;

    const RESOLUTION = 'PT1M';

    public function __construct(Task $root) {
        $this->root = $root;
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $until
     * @return Schedule
     */
    abstract public function createSchedule(\DateTime $from, \DateTime $until);

} 