<?php
namespace spec\rtens\xkdl\fixtures;

use rtens\xkdl\web\RootResource;
use Symfony\Component\Yaml\Tests\A;
use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\error\HttpError;
use watoki\curir\protocol\Url;
use watoki\curir\Responder;
use watoki\deli\filter\DefaultFilterRegistry;
use watoki\deli\filter\FilterRegistry;
use watoki\deli\Path;
use watoki\scrut\Fixture;

/**
 * @property ConfigFixture config <-
 * @property FileFixture file <-
 * @property SessionFixture s <-
 */
class WebInterfaceFixture extends Fixture {

    /** @var WebResponse */
    private $response;

    /** @var array */
    private $parameters = array();

    /** @var array */
    private $accept = array('html');

    /** @var null|\Exception */
    private $caught;

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
        $this->whenICallTheResource_WithTheMethod($path, WebRequest::METHOD_GET);
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
        $root = $this->spec->factory->getInstance(RootResource::$CLASS);
        $this->spec->factory->setSingleton(FilterRegistry::$CLASS, new DefaultFilterRegistry());

        $request = new WebRequest(Url::fromString('http://xkdl'), Path::fromString($path), $method, new Map($this->parameters),
                new Liste($this->accept), null);
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

    public function thenTheHeader_WithTheValue_ShouldBeSet($key, $value) {
        $this->spec->assertTrue($this->response->getHeaders()->has($key));
        $this->spec->assertEquals($value, $this->response->getHeaders()->get($key));
    }

    public function thenTheResponseBodyShouldContain($string) {
        $this->spec->assertContains($string, $this->response->getBody());
    }
}