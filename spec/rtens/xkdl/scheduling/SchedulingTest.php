<?php
namespace spec\rtens\xkdl\scheduling;

use rtens\xkdl\lib\Schedule;
use rtens\xkdl\Scheduler;
use rtens\xkdl\Task;
use spec\rtens\xkdl\fixtures\TaskFixture;
use watoki\scrut\Specification;

/**
 * @property Schedule schedule
 * @property \Exception|null caught
 *
 * @property TaskFixture task <-
 */
abstract class SchedulingTest extends Specification {

    /**
     * @param \rtens\xkdl\Task $root
     * @return Scheduler
     */
    abstract protected function createSchedulerInstance(Task $root);

    protected function whenICreateTheSchedule() {
        $scheduler = $this->createSchedulerInstance($this->task->root);
        $this->schedule = $scheduler->createSchedule(new \DateTime(), $this->aligned('2 hours'));
    }

    protected function thenThereShouldBe_SlotsInTheSchedule($count) {
        $this->assertCount($count, $this->schedule->slots);
    }

    protected function thenSlot_ShouldBeTask($index, $name) {
        $this->assertEquals($name, $this->schedule->slots[$index - 1]->task->getName());
    }

    protected function thenSlot_ShouldBe_Minutes($index, $minutes) {
        $this->assertEquals($minutes, $this->schedule->slots[$index - 1]->window->getSeconds() / 60);
    }

    protected function thenSlot_ShouldStart($index, $when) {
        $this->assertEquals($this->aligned($when), $this->schedule->slots[$index - 1]->window->start);
    }

    protected function aligned($from) {
        return new \DateTime(date('Y-m-d H:i:0', strtotime($from)));
    }

    protected function whenITryToRepeatTheWindowsOf($task) {
        try {
            $this->task->givenTheWindowsOf_AreRepeatedEvery_Minutes($task, 1);
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    protected function thenAnExceptionShouldBeThrown() {
        $this->assertNotNull($this->caught);
    }

} 