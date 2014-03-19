<?php
namespace rtens\xkdl\web;

use rtens\xkdl\lib\TimeWindow;
use rtens\xkdl\Scheduler;
use rtens\xkdl\storage\Reader;
use rtens\xkdl\storage\Writer;
use watoki\curir\resource\DynamicResource;

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
                ) : false
        ));
    }

    public function doStart($task, \DateTime $start, $end = null) {
        if ($end) {
            $this->writer->addLog($task, new TimeWindow($start, new \DateTime($end)));
        } else {
            $this->writer->startLogging($task, $start);
        }
        return $this->doGet();
    }

    public function doStop(\DateTime $end) {
        $this->writer->stopLogging($end);
        return $this->doGet();
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