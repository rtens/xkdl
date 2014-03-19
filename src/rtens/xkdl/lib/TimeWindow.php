<?php
namespace rtens\xkdl\lib;

class TimeWindow {

    /** @var \DateTime */
    public $start;

    /** @var \DateTime */
    public $end;

    function __construct(\DateTime $start, \DateTime $end) {
        $this->start = $start;
        $this->end = $end;
    }

    public function getInterval() {
        return $this->end->diff($this->start);
    }

    public function getSeconds() {
        return $this->end->getTimestamp() - $this->start->getTimestamp();
    }

}