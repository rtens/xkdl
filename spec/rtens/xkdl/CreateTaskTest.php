<?php
namespace spec\rtens\xkdl;

use rtens\xkdl\web\root\ScheduleResource;
use spec\rtens\xkdl\fixtures\ResourceFixture;
use spec\rtens\xkdl\fixtures\TaskFixture;
use watoki\scrut\Specification;

/**
 * @property ResourceFixture resource <-
 * @property TaskFixture task <-
 */
class CreateTaskTest extends Specification {

    function testSuccessfulCreation() {
        $this->givenIHaveEnteredThe('task', '/some/new/task');
        $this->givenIHaveEnteredThe('deadline', '2001-01-01T12:00');
        $this->givenIHaveEnteredThe('duration', '2:42');
        $this->givenIHaveEnteredThe('description', 'Some description');

        $this->whenICreateANewTask();

        $this->task->thenThereShouldBeATask('some/new/task');
        $this->task->then_ShouldHaveTheDeadline('some/new/task', '2001-01-01 12:00');
        $this->task->then_ShouldHaveTheDuration_HoursAnd_Minutes('some/new/task', 2, 42);
        $this->task->then_ShouldHaveTheDescription('some/new/task', 'Some description');
    }

    function testMissingName() {
        $this->markTestIncomplete();
    }

    function testExistingTask() {
        $this->markTestIncomplete();
    }

    function testDurationInDecimalHours() {
        $this->markTestIncomplete();
    }

    function testInvalidDurationFormat() {
        $this->markTestIncomplete();
    }

    ######################### SET-UP ###############################

    private $params;

    protected function setUp() {
        parent::setUp();
        $this->task->useTaskStore();

        $this->params = [
            'deadline' => null,
            'duration' => null,
            'description' => null
        ];
    }


    private function givenIHaveEnteredThe($field, $value) {
        $this->params[$field] = $value;
    }

    private function whenICreateANewTask() {
        $this->resource->givenTheResourceIs(ScheduleResource::$CLASS);
        $this->resource->whenIDo(function (ScheduleResource $r) {
            return $r->createTask(
                $this->params['task'],
                new \DateTime($this->params['deadline']),
                $this->params['duration'],
                $this->params['description']
            );
        });
    }

} 