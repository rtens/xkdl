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

    public function whenIDo($callback) {
        $this->returned = $callback($this->resource);
    }

    public function whenIInvoke($methodName) {
        $this->whenIInvoke_With($methodName, []);
    }

    public function whenIInvoke_With($methodName, $params) {
        $this->returned = call_user_func_array(array($this->resource, $methodName), $params);
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
                if (!is_array($model) || !array_key_exists($fieldName, $model)) {
                    $this->spec->fail('Could not find [' . $fieldName . '] in ' . json_encode($model));
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