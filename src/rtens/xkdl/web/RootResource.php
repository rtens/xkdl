<?php
namespace rtens\xkdl\web;

use watoki\curir\resource\Container;
use watoki\curir\responder\Redirecter;

class RootResource extends Container {

    public static $CLASS = __CLASS__;

    public function doGet() {
        return new Redirecter($this->getUrl('tracking'));
    }

} 