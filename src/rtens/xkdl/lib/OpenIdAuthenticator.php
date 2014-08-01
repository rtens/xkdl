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

        /** @var \LightOpenID $openId */
        $openId = $factory->getInstance('LightOpenID', [$this->config->getHost()]);
        $this->openId = $openId;
        $openIds = $this->config->getOpenIds();

        if (!is_array($openIds) || empty($openIds)) {
            throw new \Exception('Configuration error: No openIDs provided');
        }

        $this->openId->returnUrl = $this->config->getRootUrl() . '/user?method=login';
        $this->openId->__set('identity', $openIds[0]);
    }

    public function isAuthenticated() {
        return $this->openId->validate() && in_array($this->openId->__get('identity'), $this->config->getOpenIds());
    }

    /**
     * @return Url
     */
    public function getAuthenticationUrl() {
        $in = $this->openId->authUrl();
        $url = Url::parse($in);
        if ($url->toString() != $in) {
            var_dump($url->toString(), $in);
            die('WRONG');
        }
        return $url;
    }
}