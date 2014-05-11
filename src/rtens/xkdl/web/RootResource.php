<?php
namespace rtens\xkdl\web;

use rtens\xkdl\exception\AuthenticationException;
use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\http\Url;
use watoki\curir\resource\Container;
use watoki\curir\responder\Redirecter;

class RootResource extends Container {

    public static $CLASS = __CLASS__;

    /** @var \Google_Client <- */
    public $client;

    /** @var Session <- */
    public $session;

    public function respond(Request $request) {
        if ($this->session->has('token')) {
            $this->client->setAccessToken($this->session->get('token'));
        }

        try {
            return parent::respond($request);
        } catch (AuthenticationException $ae) {
            return (new Redirecter(Url::parse($ae->getAuthenticationUrl()), Response::STATUS_UNAUTHORIZED))
                ->createResponse($request);
        }
    }

    public function doGet() {
        return new Redirecter($this->getUrl('schedule'));
    }

    public function doAuthenticate($code) {
        $this->client->authenticate($code);
        $this->session->set('token', $this->client->getAccessToken());
        return new Redirecter($this->getUrl());
    }

} 