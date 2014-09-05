<?php
namespace rtens\xkdl\lib;

class TimeSpan extends \DateInterval {

    public static $CLASS = __CLASS__;

    public static function fromInterval(\DateInterval $interval) {
        return new TimeSpan($interval->format('P%dDT%hH%iM%sS'));
    }

    /**
     * Accepts <hh>:<mm> (hours and minutes) and <h>.<H/100> (hours as float)
     *
     * @param $string
     * @throws \InvalidArgumentException
     * @return TimeSpan
     */
    public static function parse($string) {
        if (!$string) {
            throw new \InvalidArgumentException('TimeSpan string must not be empty');
        }
        if (strpos($string, ':') !== false) {
            list($hours, $minutes) = explode(':', $string);
        } else if (!is_numeric($string)) {
            throw new \InvalidArgumentException('TimeSpan string must be numeric or hh:mm');
        } else {
            $decimal = floatval($string);
            $hours = floor($decimal);
            $minutes = (int) (($decimal - $hours) * 60);
        }
        return new TimeSpan("PT{$hours}H{$minutes}M");
    }

    public function toString() {
        $days = $this->days ? $this->days . 'D' : '';
        $time = ($this->h ? $this->h . 'H' : '') .
            ($this->i ? $this->i . 'M' : '') .
            ($this->s ? $this->s . 'S' : '');

        if (!$days && !$time) {
            $time = '0S';
        }

        return 'P' . $days . 'T' . $time;
    }

    function __toString() {
        return $this->toString();
    }

    public function seconds() {
        return $this->days * 86400 + $this->h * 3600 + $this->i * 60 + $this->s;
    }

} 