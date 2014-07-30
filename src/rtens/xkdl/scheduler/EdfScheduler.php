<?php
namespace rtens\xkdl\scheduler;

use DateTime;
use rtens\xkdl\lib\ExecutionWindow;
use rtens\xkdl\lib\Schedule;
use rtens\xkdl\lib\Slot;
use rtens\xkdl\lib;
use rtens\xkdl\Scheduler;
use rtens\xkdl\Task;

class EdfScheduler extends Scheduler {

    /**
     * @param DateTime $from
     * @param DateTime $until
     * @return Schedule
     */
    public function createSchedule(\DateTime $from, \DateTime $until) {
        $now = new \DateTime($from->format('Y-m-d H:i:0'));

        $schedule = new Schedule($from, $until);
        while ($now < $until) {
            $chosen = $this->chooseNextTask($now, $schedule);

            $next = clone $now;
            $next->add(new \DateInterval(self::RESOLUTION));

            if ($chosen) {
                if (count($schedule->slots) && $schedule->slots[count($schedule->slots) - 1]->task == $chosen
                    && $schedule->slots[count($schedule->slots) - 1]->window->end == $now
                ) {
                    $schedule->slots[count($schedule->slots) - 1]->window->end = $next;
                } else {
                    $schedule->slots[] = new Slot($chosen, new lib\TimeWindow($now, $next));
                }
            }

            $now = $next;
        }
        return $schedule;
    }

    /**
     * @param \DateTime $now
     * @param Schedule $schedule
     * @return null|Task
     */
    protected function chooseNextTask(\DateTime $now, Schedule $schedule) {
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

        return count($tasks) ? $tasks[0] : null;
    }

    protected function getSchedulableTasks(Task $root, $now) {
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
    protected function filterTasks($tasks, DateTime $now, $slots) {
        $filtered = array();
        foreach ($tasks as $task) {
            if ($task->getDuration()->seconds() > 0
                && $this->hasWindowWithFreeQuota($task, $now, $slots)
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