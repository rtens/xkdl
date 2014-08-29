<?php
namespace rtens\xkdl\lib;

class Schedule {

    /** @var \DateTime */
    private $from;

    /** @var \DateTime */
    private $until;

    /** @var array|Slot[] */
    private $slots = array();

    public function __construct(\DateTime $from, \DateTime $until) {
        $this->from = $from;
        $this->until = $until;
    }

    /**
     * @return \DateTime
     */
    public function getFrom() {
        return $this->from;
    }

    /**
     * @return \DateTime
     */
    public function getUntil() {
        return $this->until;
    }

    /**
     * @return array|\rtens\xkdl\lib\Slot[]
     */
    public function getSlots() {
        return $this->slots;
    }

    public function addSlot(Slot $s) {
        $this->slots[] = $s;
    }

    /**
     * @param $index
     * @return Slot
     */
    public function getSlot($index) {
        return $this->slots[$index];
    }


} 