<?php
namespace rtens\xkdl\lib;

use watoki\curir\http\Url;
use watoki\factory\Factory;

class OpenIdAuthenticator {

    /** @var Configuration */
    public $config;

    /** @var \LightOpenID */
    private $openId;

    public function __construct(Factory $factory, Configuration $config) {
        $this->config = $config;

        $this->openId = $factory->getInstance('LightOpenID', [$this->config->getHost()]);
        $openIds = $this->config->getOpenIds();

        if (!is_array($openIds) || empty($openIds)) {
            throw new \Exception('Configuration error: No openIDs provided');
        }

        $this->openId->__set('identity', $openIds[0]);
    }

    public function isAuthenticated() {
        return $this->openId->validate() && in_array($this->openId->__get('identity'), $this->config->getOpenIds());
    }

    /**
     * @return Url
     */
    public function getAuthenticationUrl() {
        return $this->openId->authUrl();
    }
}