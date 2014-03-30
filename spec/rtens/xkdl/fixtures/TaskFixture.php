<?php
namespace spec\rtens\xkdl\fixtures;

use rtens\xkdl\lib\ExecutionWindow;
use rtens\xkdl\lib\TimeSpan;
use rtens\xkdl\lib\TimeWindow;
use rtens\xkdl\task\RepeatingTask;
use rtens\xkdl\Task;
use watoki\scrut\Fixture;

class TaskFixture extends Fixture {

    /** @var Task */
    public $root;

    /** @var Task[]|RepeatingTask[] */
    private $tasks = array();

    private function getTask($path, $task = null) {
        $task = $task ?: $this->root;
        foreach (explode('/', $path) as $name) {
            $task = $task->getChild($name);
        }
        return $task;
    }

    public function givenTheRootTask($name) {
        $this->root = new Task($name);
        $this->tasks[$name] = $this->root;
    }

    public function givenTheTask_In($child, $parent) {
        $this->tasks[$child] = new Task($child, new TimeSpan('PT1M'));
        $this->tasks[$parent]->addChild($this->tasks[$child]);
    }

    public function thenThereShouldBeATask($path) {
        $this->spec->assertNotNull($this->getTask($path));
    }

    public function then_ShouldBeDone($path) {
        $this->spec->assertTrue($this->getTask($path)->isDone());
    }

    public function then_ShouldTake_Minutes($path, $duration) {
        $expected = new TimeSpan('PT' . $duration . 'M');
        $this->spec->assertEquals($expected->seconds(), $this->getTask($path)->getDuration()->seconds());
    }

    public function then_ShouldHaveTheDeadline($path, $deadline) {
        $this->spec->assertEquals(new \DateTime($deadline), $this->getTask($path)->getDeadline());
    }

    public function then_ShouldBeARepeatingTask($path) {
        $this->spec->assertTrue($this->getTask($path) instanceof RepeatingTask);
    }

    public function thenTheRepetitionOf_ShouldBe($path, $interval) {
        /** @var \rtens\xkdl\task\RepeatingTask $repeatingTask */
        $repeatingTask = $this->getTask($path);
        $this->spec->assertEquals(new \DateInterval($interval), $repeatingTask->getRepetition());
    }

    public function then_ShouldHave_Windows($path, $count) {
        $this->spec->assertCount($count, $this->getTask($path)->getWindows());
    }

    public function then_ShouldHave_Logs($path, $count) {
        $this->spec->assertCount($count, $this->getTask($path)->getLogs());
    }

    public function then_ShouldHaveNoChildren($path) {
        $this->spec->assertEmpty($this->getTask($path)->getChildren());
    }

    public function then_ShouldBeOpen($task) {
        $this->spec->assertFalse($this->getTask($task)->isDone());
    }

    public function then_ShouldHaveThePriority($task, $priority) {
        $this->spec->assertEquals($priority, $this->getTask($task)->getPriority());
    }

    public function givenTheRepeatingTask_In($task, $parent) {
        $this->tasks[$task] = new RepeatingTask($task, new TimeSpan('PT1M'));
        $this->tasks[$parent]->addChild($this->tasks[$task]);
    }

    public function given_HasTheDeadline($task, $deadline) {
        $this->tasks[$task]->setDeadline(new \DateTime($deadline));
    }

    public function given_Takes_Minutes($name, $minutes) {
        $this->tasks[$name]->setDuration(new TimeSpan('PT' . $minutes . 'M'));
    }

    public function givenIHaveLogged_MinutesFor($minutes, $name) {
        $this->tasks[$name]->addLog(new TimeWindow(new \DateTime(), new \DateTime($minutes . ' minutes')));
    }

    public function given_HasAWindowFrom_Until($task, $from, $until) {
        $this->tasks[$task]->addWindow(new ExecutionWindow($this->aligned($from), $this->aligned($until)));
    }

    public function given_HasAWindowFrom_Until_WithAQuotaOf_Minutes($task, $from, $until, $quota) {
        $this->tasks[$task]->addWindow(new ExecutionWindow($this->aligned($from), $this->aligned($until), $quota / 60));
    }

    public function given_DependsOn($task, $dependency) {
        $this->tasks[$task]->addDependency($this->tasks[$dependency]);
    }

    public function given_IsRepeatedEach_Minutes($task, $minutes) {
        $this->tasks[$task]->repeatEvery(new \DateInterval('PT' . $minutes . 'M'));
    }

    public function given_IsDone($task) {
        $this->tasks[$task]->setDone();
    }

    public function givenTheWindowsOf_AreRepeatedEvery_Minutes($task, $minutes) {
        $this->tasks[$task]->repeatWindow(new \DateInterval("PT{$minutes}M"));
    }

    private function aligned($from) {
        return new \DateTime(date('Y-m-d H:i:0', strtotime($from)));
    }

    public function given_HasThePriority($task, $priority) {
        $this->tasks[$task]->setPriority($priority);
    }

}