<?php
namespace rtens\xkdl\lib;

class Time {

    /**
     * @return \DateTime
     */
    public function now() {
        return new \DateTime();
    }

    /**
     * @param string $when
     * @return \DateTime
     */
    public function then($when) {
        $time = new \DateTime();
        $time->setTimestamp(strtotime($when, $this->now()->getTimestamp()));
        return $time;
    }

} 