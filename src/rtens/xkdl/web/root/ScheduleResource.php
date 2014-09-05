<?php
namespace rtens\xkdl\web\root;

use rtens\xkdl\lib\Configuration;
use rtens\xkdl\lib\TimeSpan;
use rtens\xkdl\lib\TimeWindow;
use rtens\xkdl\scheduler\SchedulerFactory;
use rtens\xkdl\storage\TaskStore;
use rtens\xkdl\storage\Writer;
use rtens\xkdl\Task;
use rtens\xkdl\web\filters\TimeSpanFilter;
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

    /** @var SchedulerFactory <- */
    public $schedulerFactory;

    public function respond(Request $request) {
        $this->session->requireLoggedIn($this);
        $this->filters->registerFilter(TimeSpan::$CLASS, new TimeSpanFilter());
        return parent::respond($request);
    }

    public function doGet($task = '') {
        return new Presenter($this, $this->assembleModel($task));
    }

    public function doCreateTask($task, TimeSpan $duration = null, \DateTime $deadline = null, $description = null) {
        $task = trim($task);
        if (!$task) {
            return new Presenter($this, $this->assembleModel($task, [
                'error' => [
                    'message' => 'Could not create task. No name given.'
                ]
            ]));
        }

        $this->writer->create($task);

        $newTask = $this->store->getTask($task);

        if ($duration) {
            $newTask->setDuration($duration);
        } else {
            $newTask->setDuration($this->config->defaultDuration());
        }
        if ($deadline) {
            $newTask->setDeadline($deadline);
        }
        if ($description){
            $newTask->setDescription($description);
        }

        $this->writer->update($newTask);

        return new Presenter($this, $this->assembleModel($task, [
            'created' => [
                'task' => $task
            ]
        ]));
    }

    public function doPost(\DateTime $from, \DateTime $until, $scheduler) {
        $root = $this->store->getRoot();

        $schedule = $this->schedulerFactory->create($scheduler, $root)->createSchedule($from, $until);

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
        foreach ($schedule->getSlots() as $slot) {
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
                    'duration' => $this->assembleDuration($slot->task),
                    'description' => $slot->task->getDescription()
                            ? ['text' => \Parsedown::instance()->text($slot->task->getDescription())]
                            : null
                )
            );
        }
        return array(
            'from' => $schedule->getFrom()->format('Y-m-d H:i'),
            'until' => $schedule->getUntil()->format('Y-m-d H:i'),
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

    private function assembleSchedulers() {
        $schedulers = array();
        foreach ($this->schedulerFactory->all() as $key => $info) {
            $schedulers[] = [
                'name' => $info['name'],
                'description' => $info['description'],
                'meta' => [
                    'value' => $key,
                    'checked' => ($this->config->defaultSchedulerKey() == $key ? 'checked' : false)
                ]
            ];
        }
        return $schedulers;
    }

    /**
     * @param $task
     * @param array $merge
     * @return array
     */
    private function assembleModel($task, $merge = []) {
        $root = $this->store->getRoot();
        $logging = $this->writer->getOngoingLogInfo();
        $model = array(
            'task' => array('value' => $task),
            'idle' => !$logging ? array(
                    'taskList' => 'var taskList = ' . json_encode($this->getOpenTasksOf($root))
                ) : null,
            'logging' => $logging ? array(
                    'task' => array('value' => $logging['task']),
                    'start' => array('value' => $logging['start']->format('Y-m-d H:i'))
                ) : null,
            'schedule' => $this->assembleSchedule($root),
            'algorithm' => $this->assembleSchedulers(),
            'created' => null,
            'error' => null
        );
        return array_merge($model, $merge);
    }

} 