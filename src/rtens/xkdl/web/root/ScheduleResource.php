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

    public function doGet(\DateTime $until = null) {
        $until = $until ? : new \DateTime('tomorrow');

        $root = $this->reader->read();

        $logging = $this->writer->isLogging();
        return new Presenter($this, array(
            'idle' => !$logging ? array(
                    'taskList' => 'var taskList = ' . json_encode($this->getOpenTasksOf($root))
                ) : null,
            'logging' => $logging ? array(
                    'task' => array('value' => $logging['task']),
                    'start' => array('value' => date('Y-m-d H:i', strtotime($logging['start'])))
                ) : null,
            'slot' => $this->assembleSchedule($root, $until)
        ));
    }

    public function doLog($task, \DateTime $start, $end = null) {
        if ($end) {
            $this->writer->addLog($task, new TimeWindow($start, new \DateTime($end)));
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

    private function assembleSchedule(Task $root, \DateTime $until) {
        $scheduler = new Scheduler($root);
        $schedule = $scheduler->createSchedule(new \DateTime(), $until ? : new \DateTime('7 days'));

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

        $percentage = $logged / $duration * 100;
        return array(
            'number' => round($logged / 3600, 2) . ' / ' . round($duration / 3600, 2),
            'logged' => array('style' => 'width: ' . min($percentage, 100) . '%')
        );
    }

} 