<?php
namespace rtens\xkdl;

use DateTime;
use rtens\xkdl\lib\Schedule;
use rtens\xkdl\lib\Slot;

class Scheduler {

    private $root;

    const RESOLUTION = 'PT1M';

    public function __construct(Task $root) {
        $this->root = $root;
    }

    /**
     * @param DateTime $from
     * @param DateTime $until
     * @return Schedule
     */
    public function createSchedule(\DateTime $from, \DateTime $until) {
        $now = new \DateTime($from->format('Y-m-d H:i:0'));

        $schedule = new Schedule($from, $until);
        while ($now < $until) {
            $tasks = $this->root->getSchedulableTasks($now, $schedule->slots);
            usort($tasks, function (Task $a, Task $b) {
                $deadlineA = $a->getDeadline();
                $deadlineB = $b->getDeadline();
                if ($deadlineA == $deadlineB) {
                    return $a->hasHigherPriorityThen($b) ? -1 : 1;
                }
                return $deadlineA && !$deadlineB || $deadlineA && $deadlineB && $deadlineA < $deadlineB ? -1 : 1;
            });

            $next = clone $now;
            $next->add(new \DateInterval(self::RESOLUTION));

            if (count($tasks) > 0) {
                if (count($schedule->slots) && $schedule->slots[count($schedule->slots) - 1]->task == $tasks[0]
                    && $schedule->slots[count($schedule->slots) - 1]->window->end == $now
                ) {
                    $schedule->slots[count($schedule->slots) - 1]->window->end = $next;
                } else {
                    $schedule->slots[] = new Slot($tasks[0], new lib\TimeWindow($now, $next));
                }
            }

            $now = $next;
        }
        return $schedule;
    }
}