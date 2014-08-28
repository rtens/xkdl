<?php
namespace rtens\xkdl\web\root;

use rtens\xkdl\storage\TaskStore;
use rtens\xkdl\Task;
use rtens\xkdl\web\Presenter;
use rtens\xkdl\web\Session;
use watoki\curir\resource\DynamicResource;
use watoki\curir\Responder;

class LogsResource extends DynamicResource {

    public static $CLASS = __CLASS__;

    /** @var TaskStore <- */
    public $store;

    /** @var Session <- */
    public $session;

    /**
     * @param string $task
     * @param null|string $from
     * @param null|string $until
     * @param bool $sortByTime
     * @return Presenter
     */
    public function doGet($task = '', $from = null, $until = null, $sortByTime = false) {
        $this->session->requireLoggedIn($this);

        $from = $from ? new \DateTime($from) : null;
        $until = $until ? new \DateTime($until) : null;

        $logs = array();

        if ($task) {
            $logs = $this->assembleLogs($this->store->getTask($task), $from, $until);
            if ($sortByTime) {
                usort($logs, function ($a, $b) {
                    return $a['start'] < $b['start'] ? -1 : 1;
                });
            }
        }

        $totalSeconds = array_sum(array_map(function ($l) {
            return $l['s'];
        }, $logs));

        return new Presenter($this, [
            'from' => ['value' => $from ? $from->format('Y-m-d H:i') : ''],
            'until' => ['value' => $until ? $until->format('Y-m-d H:i') : ''],
            'task' => ['value' => $task],
            'log' => $logs,
            'hasLogs' => !empty($logs),
            'total' => $this->formatSeconds($totalSeconds),
            'taskList' => 'var taskList = ' . json_encode($this->getTasksOf($this->store->getRoot()))
        ]);
    }

    private function assembleLogs(Task $under, \DateTime $from = null, \DateTime $until = null) {
        $logs = [];
        foreach ($under->getLogs() as $log) {
            if ((!$from || $log->start > $from || $log->end > $from)
                && (!$until || $log->start < $until || $log->end < $until)
            ) {
                $logs[] = array(
                    'task' => $under->getFullName(),
                    'start' => $log->start->format('Y-m-d H:i'),
                    'end' => $log->end->format('Y-m-d H:i'),
                    'time' => $this->formatSeconds($log->end->getTimestamp() - $log->start->getTimestamp()),
                    's' => $log->end->getTimestamp() - $log->start->getTimestamp()
                );
            }
        }

        foreach ($under->getChildren() as $child) {
            $logs = array_merge($logs, $this->assembleLogs($child, $from, $until));
        }

        return $logs;
    }

    private function formatSeconds($seconds) {
        $hours = $seconds / 3600;
        $fullHours = intval($seconds / 3600);
        $minutes = intval(($seconds % 3600) / 60);

        return sprintf('%d:%02d (%.2f)', $fullHours, $minutes, $hours);
    }

    private function getTasksOf(Task $task) {
        $tasks = array();
        foreach ($task->getChildren() as $child) {
            $tasks[] = $child->getFullName();
        }
        foreach ($task->getChildren() as $child) {
            $tasks = array_merge($tasks, $this->getTasksOf($child));
        }
        return $tasks;
    }
}