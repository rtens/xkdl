<?php
namespace spec\rtens\xkdl\fixtures;

use watoki\curir\http\Url;
use watoki\curir\Resource;
use watoki\curir\Responder;
use watoki\curir\responder\Presenter;
use watoki\scrut\Fixture;

/**
 * @property SessionFixture s <-
 */
class ResourceFixture extends Fixture {

    /** @var Resource */
    private $resource;

    /** @var Responder|mixed */
    private $returned;

    public function givenTheResourceIs($className) {
        $this->resource = $this->spec->factory->getInstance($className, [Url::parse('http://xkdl')]);
    }

    public function whenIInvoke($methodName) {
        $this->returned = call_user_func(array($this->resource, $methodName));
    }

    public function then_ShouldBe($field, $value) {
        $this->spec->assertEquals($value, $this->getField($field));
    }

    public function then_ShouldHaveTheSize($field, $size) {
        $this->spec->assertCount($size, $this->getField($field));
    }

    private function getField($path) {
        if ($this->returned instanceof Presenter) {
            $model = $this->returned->getModel();
            foreach (explode('/', $path) as $fieldName) {
                if (!array_key_exists($fieldName, $model)) {
                    $this->spec->fail('Could not find [' . $fieldName . '] in ' . print_r($model, true));
                }
                $model = $model[$fieldName];
            }
            return $model;
        } else {
            $this->spec->fail('Response is not a Presenter, but ' . print_r($this->returned, true));
            return null;
        }
    }

} 