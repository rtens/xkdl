<?php
namespace rtens\xkdl\web;

use rtens\xkdl\Scheduler;
use rtens\xkdl\storage\Reader;
use watoki\curir\resource\DynamicResource;

class RootResource extends DynamicResource {

    public static $CLASS = __CLASS__;

    public function doGet(\DateTime $until = null) {
        $reader = new Reader(ROOT . '/user/root');
        $scheduler = new Scheduler($reader->read());
        $schedule = $scheduler->createSchedule(new \DateTime(), $until ?: new \DateTime('7 days'));

        $model = array();
        foreach ($schedule as $slot) {
            $model[] = $slot->task->getName() . ' ('
                . $slot->window->start->format('Y-m-d H:i') . ' >> ' . $slot->window->end->format('Y-m-d H:i') . ')';
        }
        echo '<pre>';
        var_dump($model);
    }

} 