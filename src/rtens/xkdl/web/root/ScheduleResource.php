<?php
namespace rtens\xkdl\web\root;

use rtens\xkdl\lib\Configuration;
use rtens\xkdl\lib\TimeWindow;
use rtens\xkdl\scheduler\EdfScheduler;
use rtens\xkdl\scheduler\PrioritizedEdfScheduler;
use rtens\xkdl\storage\TaskStore;
use rtens\xkdl\storage\Writer;
use rtens\xkdl\Task;
use rtens\xkdl\web\Presenter;
use rtens\xkdl\web\Session;
use watoki\curir\http\Request;
use watoki\curir\resource\DynamicResource;
use watoki\curir\responder\Redirecter;
use watoki\curir\Responder;
use watoki\dom\Element;

class ScheduleResource extends DynamicResource {

    public static $CLASS = __CLASS__;

    /** @var Writer <- */
    public $writer;

    /** @var TaskStore <- */
    public $store;

    /** @var Configuration <- */
    public $config;

    /** @var Session <- */
    public $session;

    public function respond(Request $request) {
        $this->session->requireLoggedIn();
        return parent::respond($request);
    }

    public function doGet($task = '') {
        $root = $this->store->getRoot();

        $logging = $this->writer->getOngoingLogInfo();
        return new Presenter($this, array(
            'task' => array('value' => $task),
            'idle' => !$logging ? array(
                    'taskList' => 'var taskList = ' . json_encode($this->getOpenTasksOf($root))
                ) : null,
            'logging' => $logging ? array(
                    'task' => array('value' => $logging['task']),
                    'start' => array('value' => $logging['start']->format('Y-m-d H:i'))
                ) : null,
            'schedule' => $this->assembleSchedule($root)
        ));
    }

    public function doPost(\DateTime $from, \DateTime $until, $scheduler = 'pedf') {
        $root = $this->store->getRoot();

        if ($scheduler == 'edf') {
            $scheduler = new EdfScheduler($root);
        } else {
            $scheduler = new PrioritizedEdfScheduler($root);
        }

        $schedule = $scheduler->createSchedule($from, $until);

        $this->writer->saveSchedule($schedule);
        return new Redirecter($this->getUrl());
    }

    public function doStart($task, \DateTime $start, $end = null) {
        if ($end) {
            $this->writer->addLog($task, new TimeWindow($start, new \DateTime($end)));
        } else {
            if ($this->writer->getOngoingLogInfo()) {
                $this->writer->stopLogging($start);
            }
            $this->writer->startLogging($task, $start);
        }
        return new Redirecter($this->getUrl());
    }

    public function doStop(\DateTime $end) {
        $task = $this->writer->stopLogging($end);
        $url = $this->getUrl();
        $url->getParameters()->set('task', $task);
        return new Redirecter($url);
    }

    public function doCancel() {
        $this->writer->cancelLogging();
        return new Redirecter($this->getUrl());
    }

    public function doDone($task) {
        $this->writer->markDone($task);
        return new Redirecter($this->getUrl());
    }

    public function doOpen($task) {
        $this->writer->markOpen($task);
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

    private function assembleSchedule($root) {
        try {
            $schedule = $this->writer->readSchedule($root);
        } catch (\Exception $e) {
            return array(
                'from' => date('Y-m-d H:i'),
                'until' => date('Y-m-d H:i'),
                'slot' => array()
            );
        }

        $slots = array();
        foreach ($schedule->slots as $slot) {
            $isActive = !$slot->task->isDone() && $slot->task->getDeadline();
            $isLate = $isActive && $slot->window->end > $slot->task->getDeadline();

            $markDone = function (Element $e) use ($slot, $isLate) {
                if ($slot->task->isDone()) {
                    if ($e->getAttribute('value')) {
                        $e->setAttribute('value', 'open');
                    }
                    return str_replace(array('info', 'warning'), 'success',
                        $e->getAttribute('class')->getValue());
                } else if ($isLate) {
                    return str_replace(array('info', 'warning'), 'danger',
                        $e->getAttribute('class')->getValue());
                } else {
                    return $e->getAttribute('class')->getValue();
                }
            };

            $slots[] = array(
                'class' => $markDone,
                'done' => array('class' => $markDone),
                'start' => $slot->window->start->format('H:i'),
                'end' => $slot->window->end->format('H:i'),
                'isLate' => $isLate,
                'task' => array(
                    'name' => $slot->task->getName(),
                    'target' => array('value' => $slot->task->getFullName()),
                    'parent' => $slot->task->getParent()->getFullName(),
                    'deadline' => $isActive ? array(
                            'relative' => $slot->task->getDeadline()->diff($this->config->now())->format('%ad %hh %im'),
                            'buffer' => $slot->task->getDeadline()->diff($slot->window->end)->format('%ad %hh %im'),
                            'absolute' => $slot->task->getDeadline()->format('Y-m-d H:i')
                        ) : null,
                    'duration' => $this->assembleDuration($slot->task)
                )
            );
        }
        return array(
            'from' => $schedule->from->format('Y-m-d H:i'),
            'until' => $schedule->until->format('Y-m-d H:i'),
            'slot' => $slots
        );
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