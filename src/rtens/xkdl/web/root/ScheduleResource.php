<?php
namespace rtens\xkdl\web\root;

use rtens\xkdl\lib\TimeWindow;
use rtens\xkdl\Scheduler;
use rtens\xkdl\storage\Reader;
use rtens\xkdl\storage\Writer;
use rtens\xkdl\Task;
use rtens\xkdl\web\Presenter;
use watoki\curir\resource\DynamicResource;
use watoki\curir\responder\Redirecter;

class ScheduleResource extends DynamicResource {

    public static $CLASS = __CLASS__;

    /** @var Writer <- */
    public $writer;

    /** @var Reader <- */
    public $reader;

    public function doGet($from = null, $until = null) {
        $until = $until ? new \DateTime($until) : new \DateTime('tomorrow');
        $from = $from ? new \DateTime($from) : new \DateTime('now');

        $root = $this->reader->read();

        $logging = $this->writer->getOngoingLogInfo();
        return new Presenter($this, array(
            'idle' => !$logging ? array(
                    'taskList' => 'var taskList = ' . json_encode($this->getOpenTasksOf($root))
                ) : null,
            'logging' => $logging ? array(
                    'task' => array('value' => $logging['task']),
                    'start' => array('value' => $logging['start']->format('Y-m-d H:i'))
                ) : null,
            'slot' => $this->assembleSchedule($root, $from, $until)
        ));
    }

    public function doLog($task, \DateTime $start, $end = null) {
        if ($end) {
            $this->writer->addLog($task, new TimeWindow($start, new \DateTime($end)));
        } else if ($this->writer->getOngoingLogInfo()) {
            throw new \Exception("Can't start an ongoing log if another task is being logged.");
        } else {
            $this->writer->startLogging($task, $start);
        }
        return new Redirecter($this->getUrl());
    }

    public function doFinish(\DateTime $end) {
        $this->writer->stopLogging($end);
        return new Redirecter($this->getUrl());
    }

    public function doCancel() {
        $this->writer->cancelLogging();
        return new Redirecter($this->getUrl());
    }

    private function getOpenTasksOf(Task $task) {
        $tasks = array();
        foreach ($task->getOpenChildren() as $child) {
            $tasks[] = utf8_encode($child->getFullName());
        }
        foreach ($task->getOpenChildren() as $child) {
            $tasks = array_merge($tasks, $this->getOpenTasksOf($child));
        }
        return $tasks;
    }

    private function assembleSchedule(Task $root, \DateTime $from, \DateTime $until) {
        $scheduler = new Scheduler($root);
        $schedule = $scheduler->createSchedule($from, $until);

        $model = array();
        foreach ($schedule as $slot) {
            $model[] = array(
                'start' => $slot->window->start->format('H:i'),
                'end' => $slot->window->end->format('H:i'),
                'task' => array(
                    'name' => $slot->task->getName(),
                    'parent' => $slot->task->getParent()->getFullName(),
                    'deadline' => $slot->task->getDeadline() ? array(
                            'relative' => $slot->task->getDeadline()->diff(new \DateTime())->format('%ad %hh %im'),
                            'absolute' => $slot->task->getDeadline()->format('Y-m-d H:i')
                        ) : null,
                    'duration' => $this->assembleDuration($slot->task)
                )
            );
        }
        return $model;
    }

    private function assembleDuration(Task $task) {
        $duration = $task->getDuration()->seconds();
        if (!$duration) {
            return null;
        }

        $logged = $task->getLoggedDuration()->seconds();

        $percentage = round($logged / $duration * 100, 2);
        return array(
            'number' => round($logged / 3600, 2) . ' / ' . round($duration / 3600, 2),
            'logged' => array('style' => 'width: ' . min($percentage, 100) . '%')
        );
    }

} 