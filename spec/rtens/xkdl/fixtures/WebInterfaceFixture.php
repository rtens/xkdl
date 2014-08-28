<?php
namespace spec\rtens\xkdl\fixtures;

use rtens\mockster\Mock;
use rtens\mockster\MockFactory;
use rtens\mockster\Mockster;
use rtens\xkdl\web\RootResource;
use rtens\xkdl\web\Session;
use watoki\collections\Map;
use watoki\curir\http\error\HttpError;
use watoki\curir\http\Path;
use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\http\Url;
use watoki\curir\Responder;
use watoki\scrut\Fixture;

/**
 * @property ConfigFixture config <-
 * @property FileFixture file <-
 */
class WebInterfaceFixture extends Fixture {

    /** @var Response */
    private $response;

    /** @var array */
    private $parameters = array();

    /** @var array */
    private $accept = array('html');

    /** @var Session|Mock */
    private $session;

    /** @var null|\Exception */
    private $caught;

    public function setUp() {
        parent::setUp();

        $mf = new MockFactory();
        $this->session = $mf->getInstance(Session::$CLASS);
        $this->spec->factory->setSingleton(Session::$CLASS, $this->session);
        $this->session->__mock()->mockMethods(Mockster::F_NONE);

        /** @var Session $session */
        $session = $this->session;
        $session->config = $this->config->getConfig();
        $session->setLoggedIn();
    }

    public function givenIAmNotLoggedIn() {
        $this->session->setLoggedIn(false);
    }

    public function givenIAmLoggedIn() {
        $this->session->setLoggedIn();
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

    public function whenITryToCallTheResource_WithTheMethod($path, $method) {
        try {
            $this->whenICallTheResource_WithTheMethod($path, $method);
        } catch (\Exception $e) {
            $this->caught = $e;
        }
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

    public function thenIShouldBeLoggedIn() {
        $this->spec->assertTrue($this->session->isLoggedIn(), 'Not logged in');
    }

    public function thenIShouldNotBeLoggedIn() {
        $this->spec->assertFalse($this->session->isLoggedIn(), 'Logged in');
    }

    public function thenAnErrorWithTheStatus_ShouldOccur($status) {
        $this->spec->assertNotNull($this->caught, 'No Exception was thrown.');
        if ($this->caught instanceof HttpError) {
            $this->spec->assertEquals($status, $this->caught->getStatus());
        } else {
            $this->spec->fail('Not an HttpError: ' . $this->caught->getMessage());
        }
    }
}