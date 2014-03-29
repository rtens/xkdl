<?php
namespace spec\rtens\xkdl\fixtures;

use rtens\xkdl\web\RootResource;
use rtens\xkdl\web\Session;
use watoki\collections\Map;
use watoki\curir\http\Path;
use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\http\Url;
use watoki\curir\Responder;
use watoki\curir\responder\Redirecter;
use watoki\scrut\Fixture;

/**
 * @property ConfigFixture config <-
 */
class WebInterfaceFixture extends Fixture {

    /** @var Response */
    private $response;

    /** @var RootResource */
    private $root;

    /** @var array */
    private $parameters = array();

    /** @var array */
    private $accept = array('html');

    /** @var Session */
    private $session;

    protected function setUp() {
        parent::setUp();
        $this->session = new Map();
        $this->spec->factory->setSingleton(Session::CLASS, $this->session);

        $this->root = $this->spec->factory->getInstance(RootResource::CLASS, [Url::parse('http://xkdl')]);
    }

    public function givenTheSessionContains_WithTheValue($key, $value) {
        $this->session->set($key, $value);
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

    public function whenICallTheResource_WithTheMethod($path, $method) {
        $request = new Request(Path::parse($path), $this->accept, $method, new Map($this->parameters));
        $this->response = $this->root->respond($request);
    }

    public function thenTheSessionShouldContain_WithTheValue($key, $value) {
        $this->spec->assertEquals($value, $this->session->get($key));
    }
}