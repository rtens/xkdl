<?php
namespace rtens\xkdl;

use DateTime;
use rtens\xkdl\lib\ExecutionWindow;
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
            $tasks = $this->getSchedulableTasks($this->root, $now);
            $tasks = $this->filterTasks($tasks, $now, $schedule->slots);

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

    private function getSchedulableTasks(Task $root, $now) {
        $tasks = array();
        foreach ($root->getSchedulableChildren($now) as $child) {
            foreach ($this->getSchedulableTasks($child, $now) as $task) {
                $tasks[] = $task;
            }
            $tasks[] = $child;
        }
        return $tasks;
    }

    /**
     * @param Task[] $tasks
     * @param \DateTime $now
     * @param Slot[] $slots
     * @return Task[] array
     */
    private function filterTasks($tasks, DateTime $now, $slots) {
        $filtered = array();
        foreach ($tasks as $task) {
            if ($this->hasWindowWithFreeQuota($task, $now, $slots)
                && $this->areAllDependenciesScheduled($task, $slots)
                && $this->hasUnscheduledDuration($task, $slots)
                && !$task->hasOpenChildren()
            ) {
                $filtered[] = $task;
            }

        }
        return $filtered;
    }

    /**
     * @param Task $task
     * @param \DateTime $now
     * @param Slot[] $slots
     * @return bool
     */
    private function hasWindowWithFreeQuota($task, DateTime $now, $slots) {
        if (!$task->getWindows()) {
            return true;
        }

        foreach ($task->getWindows() as $window) {
            if ($window->start <= $now && $now < $window->end
                && $this->isScheduledTimeSmallerThanQuota($task, $window, $slots)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Task $task
     * @param \rtens\xkdl\lib\ExecutionWindow $window
     * @param array|Slot[] $slots
     * @return bool
     */
    private function isScheduledTimeSmallerThanQuota(Task $task, ExecutionWindow $window, $slots) {
        if (!$window->quota) {
            return true;
        }

        $secondsScheduledInWindow = 0;
        foreach ($slots as $slot) {
            if ($slot->task == $task
                && $window->start <= $slot->window->start
                && $slot->window->end <= $window->end
            ) {
                $secondsScheduledInWindow += $slot->window->getSeconds();
            }
        }
        return $secondsScheduledInWindow < $window->quota * 3600;
    }

    /**
     * @param Task $task
     * @param array|Slot[] $slots
     * @return bool
     */
    private function areAllDependenciesScheduled(Task $task, $slots) {
        foreach ($task->getDependencies() as $dependency) {
            if ($this->hasUnscheduledDuration($dependency, $slots)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Task $task
     * @param array|Slot[] $slots
     * @return float
     */
    private function hasUnscheduledDuration(Task $task, array $slots) {
        $unscheduledSeconds = $task->getDuration()->seconds() - $task->getLoggedDuration()->seconds();
        foreach ($slots as $slot) {
            if ($slot->task == $task) {
                $unscheduledSeconds -= $slot->window->getSeconds();
                if ($unscheduledSeconds <= 0) {
                    return false;
                }
            }
        }
        return true;
    }

}