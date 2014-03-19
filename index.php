<?php
use rtens\xkdl\web\TrackingResource;
use watoki\curir\WebApplication;

require_once 'bootstrap.php';

WebApplication::quickStart(TrackingResource::$CLASS);