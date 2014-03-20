<?php
use rtens\xkdl\web\RootResource;
use watoki\curir\WebApplication;

require_once 'bootstrap.php';

WebApplication::quickStart(RootResource::$CLASS);