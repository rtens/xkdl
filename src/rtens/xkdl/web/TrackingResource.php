<?php
namespace rtens\xkdl\web;

use rtens\xkdl\lib\TimeWindow;
use rtens\xkdl\Scheduler;
use rtens\xkdl\storage\Reader;
use rtens\xkdl\storage\Writer;
use rtens\xkdl\Task;
use watoki\curir\resource\DynamicResource;
use watoki\curir\responder\Redirecter;

class TrackingResource extends DynamicResource {

    public static $CLASS = __CLASS__;

    /** @var Writer <- */
    public $writer;

    public function doGet(\DateTime $until = null) {
        $logging = $this->writer->isLogging();
        return new Presenter($this, array(
            'idle' => !$logging,
            'logging' => $logging ? array(
                    'task' => array('value' => $logging['task']),
                    'start' => array('value' => date('Y-m-d H:i', strtotime($logging['start'])))
                ) : false,
            'taskList' => 'var taskList = ' . json_encode($this->getTaskList())
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

    private function getTaskList() {
        $reader = new Reader(ROOT . '/user/root');
        return $this->getAllTasksOf($reader->read());
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

    /**
     * @param \DateTime $until
     */
    private function createSchedule(\DateTime $until) {
        $reader = new Reader(ROOT . '/user/root');
        $scheduler = new Scheduler($reader->read());
        $schedule = $scheduler->createSchedule(new \DateTime(), $until ? : new \DateTime('7 days'));

        $model = array();
        foreach ($schedule as $slot) {
            $model[] = $slot->task->getName() . ' ('
                . $slot->window->start->format('Y-m-d H:i') . ' >> ' . $slot->window->end->format('Y-m-d H:i') . ')';
        }
        echo '<pre>';
        var_dump($model);
    }

} 