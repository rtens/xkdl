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

    public function doGet(\DateTime $until = null) {
        $until = $until ? : new \DateTime('tomorrow');

        $reader = new Reader(ROOT . '/user/root');
        $root = $reader->read();

        $logging = $this->writer->isLogging();
        return new Presenter($this, array(
            'idle' => !$logging ? array(
                    'taskList' => 'var taskList = ' . json_encode($this->getAllTasksOf($root))
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

    private function getAllTasksOf(Task $task) {
        $tasks = array();
        foreach ($task->getChildren() as $child) {
            $tasks[] = utf8_encode($child->getFullName());
        }
        foreach ($task->getChildren() as $child) {
            $tasks = array_merge($tasks, $this->getAllTasksOf($child));
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
        $logged = round($task->getLoggedDuration(), 1);
        $duration = $task->getDuration();

        return array(
            'number' => $logged . ' / ' . $duration,
            'logged' => array('style' => 'width: ' . ($logged / $duration * 100) . '%')
        );
    }

} 