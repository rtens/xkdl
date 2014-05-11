<?php
namespace rtens\xkdl\web\root;

use rtens\xkdl\storage\TaskStore;
use rtens\xkdl\Task;
use rtens\xkdl\web\Presenter;
use watoki\curir\resource\DynamicResource;

class LogsResource extends DynamicResource {

    /** @var TaskStore <- */
    public $store;

    public function doGet($task = '', $from = null, $until = null) {
        $from = $from ? new \DateTime($from) : null;
        $until = $until ? new \DateTime($until) : null;

        $logs = $this->assembleLogs($this->store->getTask($task), $from, $until);
        usort($logs, function ($a, $b) {
            return $a['start'] < $b['start'] ? -1 : 1;
        });

        return new Presenter($this, [
            'log' => $logs
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
                );
            }
        }

        foreach ($under->getChildren() as $child) {
            $logs = array_merge($logs, $this->assembleLogs($child, $from, $until));
        }

        return $logs;
    }
}