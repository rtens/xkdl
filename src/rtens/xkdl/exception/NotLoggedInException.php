<?php
namespace rtens\xkdl\exception;

use Exception;
use watoki\curir\protocol\Url;

class NotLoggedInException extends \Exception {

    public static $CLASS = __CLASS__;

    /** @var Url */
    private $targetUrl;

    public function __construct(Url $targetUrl) {
        parent::__construct();
        $this->targetUrl = $targetUrl;
    }

    public function getTargetUrl() {
        return $this->targetUrl;
    }

} 