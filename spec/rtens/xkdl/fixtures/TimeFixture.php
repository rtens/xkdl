<?php
namespace spec\rtens\xkdl\fixtures;

class TimeFixture {

    public function givenTheTimeZoneIs($string) {
        date_default_timezone_set($string);
    }
}