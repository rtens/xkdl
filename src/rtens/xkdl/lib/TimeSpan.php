<?php
namespace rtens\xkdl\lib;

class TimeSpan extends \DateInterval {

    public static function fromInterval(\DateInterval $interval) {
        return new TimeSpan($interval->format('P%dDT%hH%iM%sS'));
    }

    public function seconds() {
        return $this->d * 86400 + $this->h * 3600 + $this->i * 60 + $this->s;
    }

} 