<?php
namespace spec\rtens\xkdl\fixtures;

use rtens\xkdl\web\RootResource;
use Symfony\Component\Yaml\Tests\A;
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
 * @property SessionFixture s <-
 */
class WebInterfaceFixture extends Fixture {

    /** @var Response */
    private $response;

    /** @var array */
    private $parameters = array();

    /** @var array */
    private $accept = array('html');

    /** @var null|\Exception */
    private $caught;

    /** @var array */
    private $cookies = array();

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

        $request = new Request(Path::parse($path), $this->accept, $method, new Map($this->parameters),
            null, '', new Map($this->cookies));
        $this->response = $root->respond($request);
    }

    public function thenAnErrorWithTheStatus_ShouldOccur($status) {
        $this->spec->assertNotNull($this->caught, 'No Exception was thrown.');
        if ($this->caught instanceof HttpError) {
            $this->spec->assertEquals($status, $this->caught->getStatus());
        } else {
            $this->spec->fail('Not an HttpError: ' . $this->caught->getMessage());
        }
    }

    public function thenACookie_WithTheValue_ShouldBeSet($name, $value) {
        $cookies = $this->response->getCookies();
        $this->spec->assertArrayHasKey($name, $cookies);
        $this->spec->assertEquals($value, $cookies[$name]['value']);
    }

    public function givenTheCookie_WithTheValue($key, $value) {
        $this->cookies[$key] = $value;
    }

    public function thenTheHeader_WithTheValue_ShouldBeSet($key, $value) {
        $this->spec->assertTrue($this->response->getHeaders()->has($key));
        $this->spec->assertEquals($value, $this->response->getHeaders()->get($key));
    }

    public function thenTheResponseBodyShouldContain($string) {
        $this->spec->assertContains($string, $this->response->getBody());
    }
}