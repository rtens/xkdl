<?php
namespace rtens\xkdl\scheduler;

use DateTime;
use rtens\xkdl\lib\Schedule;
use rtens\xkdl\lib;
use rtens\xkdl\Task;

class PriorityScheduler extends EdfScheduler {

    protected function chooseNextTask(\DateTime $now, Schedule $schedule) {
        return $this->chooseFrom([$this->root], $now, $schedule);
    }

    /**
     * @param Task[] $tasks
     * @param DateTime $now
     * @param Schedule $schedule
     * @return null|Task
     */
    protected function chooseFrom(array $tasks, \DateTime $now, Schedule $schedule) {
        /** @var Task[] $children */
        $children = [];
        foreach ($tasks as $task) {
            $children = array_merge($children, $task->getSchedulableChildren($now));
        }

        $candidates = $this->filterTasks($children, $now, $schedule->slots);

        if (!$candidates) {
            $buckets = [];
            foreach ($children as $child) {
                $buckets[$child->getPriority()][] = $child;
            }
            ksort($buckets);

            foreach ($buckets as $bucket) {
                $chosen = $this->chooseFrom($bucket, $now, $schedule);
                if ($chosen) {
                    return $chosen;
                }
            }

            return null;
        }

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

        usort($candidates, $sort);
        return $candidates[0];
    }

}