<?php
namespace rtens\xkdl\scheduler;

use DateTime;
use rtens\xkdl\lib\Schedule;
use rtens\xkdl\lib;
use rtens\xkdl\Task;

class PrioritizedEdfScheduler extends EdfScheduler {

    protected function chooseNextTask(\DateTime $now, Schedule $schedule) {
        return $this->chooseFrom($this->root, $now, $schedule);
    }

    /**
     * @param Task $task
     * @param DateTime $now
     * @param Schedule $schedule
     * @return null|Task
     */
    protected function chooseFrom(Task $task, \DateTime $now, Schedule $schedule) {
        $children = $task->getSchedulableChildren($now);

        if (!$children) {
            return null;
        }

        $candidates = $this->filterTasks($children, $now, $schedule->slots);

        $sort = function (Task $a, Task $b) {
            $priorityA = $a->getPriority();
            $priorityB = $b->getPriority();

            if ($priorityA == $priorityB) {
                $deadlineA = $a->getDeadline();
                $deadlineB = $b->getDeadline();

                if (!$deadlineA && !$deadlineB) {
                    return strcmp($a->getName(), $b->getName());
                }

                return $deadlineA && !$deadlineB || $deadlineA && $deadlineB && $deadlineA < $deadlineB ? -1 : 1;
            }

            return $priorityA - $priorityB;
        };

        if (!$candidates) {
            usort($children, $sort);
            foreach ($children as $child) {
                $chosen = $this->chooseFrom($child, $now, $schedule);
                if ($chosen) {
                    return $chosen;
                }
            }

            return null;
        }

        usort($candidates, $sort);
        return $candidates[0];
    }

}