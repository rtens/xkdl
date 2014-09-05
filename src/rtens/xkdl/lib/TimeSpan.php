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
     * @return TimeSpan
     */
    public static function parse($string) {
        if (strpos($string, ':') !== false) {
            list($hours, $minutes) = explode(':', $string);
        } else {
            $decimal = floatval($string);
            $hours = floor($decimal);
            $minutes = ($decimal - $hours) * 60;
        }
        return new TimeSpan("PT{$hours}H{$minutes}M");
    }

    public function toString() {
        return 'P' .
        ($this->days ? $this->days . 'D' : '') .
        'T' .
        ($this->h ? $this->h . 'H' : '') .
        ($this->i ? $this->i . 'M' : '') .
        ($this->s ? $this->s . 'S' : '');
    }

    function __toString() {
        return $this->toString();
    }

    public function seconds() {
        return $this->days * 86400 + $this->h * 3600 + $this->i * 60 + $this->s;
    }

} 