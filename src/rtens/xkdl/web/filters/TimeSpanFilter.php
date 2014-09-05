<?php
namespace rtens\xkdl\web\filters;

use rtens\xkdl\lib\TimeSpan;
use watoki\factory\Filter;

class TimeSpanFilter implements Filter {

    public function filter($value) {
        return TimeSpan::parse($value);
    }
}