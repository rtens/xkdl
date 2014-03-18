<?php
namespace rtens\xkdl;

use DateTime;

class Scheduler {

    private $root;

    const RESOLUTION = 'PT1M';

    public function __construct(Task $root) {
        $this->root = $root;
    }

    /**
     * @param DateTime $from
     * @param DateTime $until
     * @return array|Task[]
     */
    public function createSchedule(\DateTime $from, \DateTime $until) {
        $now = new \DateTime($from->format('Y-m-d H:i:0'));

        /** @var Slot[] $schedule */
        $schedule = array();
        while ($now < $until) {
            $tasks = $this->root->getSchedulableTasks($now, $schedule, $until);
            usort($tasks, function (Task $a, Task $b) {
                $deadlineA = $a->getDeadline();
                return $deadlineA && $deadlineA < $b->getDeadline() ? -1 : 1;
            });

            $next = clone $now;
            $next->add(new \DateInterval(self::RESOLUTION));

            if (count($tasks) > 0) {
                if (count($schedule) && $schedule[count($schedule) - 1]->task == $tasks[0]
                    && $schedule[count($schedule) - 1]->window->end == $now
                ) {
                    $schedule[count($schedule) - 1]->window->end = $next;
                } else {
                    $schedule[] = new Slot($tasks[0], new lib\TimeWindow($now, $next));
                }
            }

            $now = $next;
        }
        return $schedule;
    }
}