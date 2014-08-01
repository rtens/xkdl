<?php
namespace rtens\xkdl\lib;

use watoki\curir\http\Url;

class OpenIdAuthenticator {

    public static $CLASS = __CLASS__;

    /** @var Configuration <- */
    public $config;

    /** @var \LightOpenID */
    private $openId;

    public function __construct() {
        $this->openId = new \LightOpenID($this->config->getHost());
        $openIds = $this->config->getOpenIds();

        if (!is_array($openIds) || empty($openIds)) {
            throw new \Exception('Configuration error: No openIDs provided');
        }

        $this->openId->__set('identity', $openIds[0]);
    }

    /**
     * @return Url
     */
    public function getAuthenticationUrl() {
        return $this->openId->authUrl();
    }
}