<?php
namespace spec\rtens\xkdl\fixtures;

use rtens\mockster\Mock;
use rtens\mockster\MockFactory;
use rtens\mockster\Mockster;
use rtens\xkdl\lib\OpenIdAuthenticator;
use rtens\xkdl\web\RootResource;
use rtens\xkdl\web\Session;
use watoki\collections\Map;
use watoki\curir\http\Path;
use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\http\Url;
use watoki\curir\Responder;
use watoki\scrut\Fixture;

/**
 * @property ConfigFixture config <-
 */
class WebInterfaceFixture extends Fixture {

    /** @var Response */
    private $response;

    /** @var array */
    private $parameters = array();

    /** @var array */
    private $accept = array('html');

    /** @var Mock|Session */
    private $session;

    public function setUp() {
        parent::setUp();

        $mf = new MockFactory();
        $this->session = $mf->getInstance(Session::$CLASS, []);
        $this->spec->factory->setSingleton(Session::$CLASS, $this->session);

        $this->session->__mock()->mockMethods(Mockster::F_NONE);
        $this->session->__mock()->method('isLoggedIn')->willReturn(true);

        $this->spec->factory->setSingleton(OpenIdAuthenticator::$CLASS,
            $mf->getInstance(OpenIdAuthenticator::$CLASS));
    }

    public function givenIAmNotLoggedIn() {
        /** @var Mockster $sessionMock */
        $sessionMock = $this->session->__mock();
        $sessionMock->method('isLoggedIn')->willReturn(false);
    }

    public function givenIAmLoggedIn() {
        /** @var Mockster $sessionMock */
        $sessionMock = $this->session->__mock();
        $sessionMock->method('isLoggedIn')->willReturn(true);
    }

    public function givenTheSessionContains_WithTheValue($key, $value) {
        $this->session->set($key, $value);
    }

    public function thenIShouldNotBeRedirected() {
        $has = $this->response->getHeaders()->has('Location');
        $this->spec->assertFalse($has,
            $has ? 'Was redirected to ' . $this->response->getHeaders()->get('Location') : '');
    }

    public function thenIShouldBeRedirectedTo($url) {
        $this->spec->assertTrue($this->response->getHeaders()->has('Location'), 'Not redirected');
        $this->spec->assertEquals($url, $this->response->getHeaders()->get('Location'));
    }

    public function thenTheResponseStatusShouldBe($status) {
        $this->spec->assertEquals($status, $this->response->getStatus());
    }

    public function givenTheParameter_Is($name, $value) {
        $this->parameters[$name] = $value;
    }

    public function whenIGetTheResource($path) {
        $this->whenICallTheResource_WithTheMethod($path, Request::METHOD_GET);
    }

    public function whenICallTheResource_WithTheMethod($path, $method) {
        /** @var RootResource $root */
        $root = $this->spec->factory->getInstance(RootResource::$CLASS, [Url::parse('http://xkdl')]);

        $request = new Request(Path::parse($path), $this->accept, $method, new Map($this->parameters));
        $this->response = $root->respond($request);
    }

    public function thenTheSessionShouldContain_WithTheValue($key, $value) {
        $this->spec->assertEquals($value, $this->session->get($key));
    }
}