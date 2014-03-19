<?php
namespace rtens\xkdl\lib;

class ExecutionWindow extends TimeWindow {

    /** @var float|null [hours] If set, only the given amount of hours is available in this window */
    public $quota;

    function __construct(\DateTime $start, \DateTime $end, $quota = null) {
        parent::__construct($start, $end);

        $this->quota = $quota;
    }

} 