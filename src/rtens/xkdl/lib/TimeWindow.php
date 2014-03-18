<?php
namespace rtens\xkdl\lib;

class TimeWindow {

    /** @var \DateTime */
    public $start;

    /** @var \DateTime */
    public $end;

    /** @var float|null [hours] If set, only the given amount of hours is available in this window */
    public $quota;

    function __construct(\DateTime $start, \DateTime $end, $quota = null) {
        $this->start = $start;
        $this->end = $end;
        $this->quota = $quota;
    }

    public function getInterval() {
        return $this->end->diff($this->start);
    }

    public function getSeconds() {
        return $this->end->getTimestamp() - $this->start->getTimestamp();
    }

}