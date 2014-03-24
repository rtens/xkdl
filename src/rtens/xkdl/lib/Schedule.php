<?php
namespace rtens\xkdl\lib;

class Schedule {

    /** @var \DateTime */
    public $from;

    /** @var \DateTime */
    public $until;

    /** @var array|Slot[] */
    public $slots = array();

    public function __construct(\DateTime $from, \DateTime $until) {
        $this->from = $from;
        $this->until = $until;
    }


} 