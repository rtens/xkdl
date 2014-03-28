<?php
namespace spec\rtens\xkdl;

use PHPUnit_Framework_TestCase;
use rtens\xkdl\lib\Schedule;
use rtens\xkdl\Scheduler;
use spec\rtens\xkdl\fixtures\TaskFixture;
use watoki\scrut\Specification;

/**
 * @property Schedule schedule
 * @property \Exception|null caught
 *
 * @property TaskFixture task <-
 */
class SchedulingTest extends Specification {

    public function background() {
        $this->task->givenTheRootTask('root');
    }

    function testEmptyRoot() {
        $this->whenICreateTheSchedule();
        $this->thenThereShouldBe_SlotsInTheSchedule(0);
    }

    function testSingleTask() {
        $this->task->givenTheTask_In('one', 'root');
        $this->whenICreateTheSchedule();
        $this->thenThereShouldBe_SlotsInTheSchedule(1);
        $this->thenSlot_ShouldBeTask(1, 'one');
    }

    function testEarliestDeadlineFirst() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->givenTheTask_In('two', 'root');
        $this->task->givenTheTask_In('three', 'root');
        $this->task->given_HasTheDeadline('one', 'tomorrow');
        $this->task->given_HasTheDeadline('two', 'next week');
        $this->task->given_HasTheDeadline('three', 'today');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBeTask(1, 'three');
        $this->thenSlot_ShouldBeTask(2, 'one');
        $this->thenSlot_ShouldBeTask(3, 'two');
    }

    function testNoDeadline() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->givenTheTask_In('two', 'root');
        $this->task->givenTheTask_In('three', 'root');
        $this->task->given_HasTheDeadline('one', 'tomorrow');
        $this->task->given_HasTheDeadline('three', 'next week');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBeTask(1, 'one');
        $this->thenSlot_ShouldBeTask(2, 'three');
        $this->thenSlot_ShouldBeTask(3, 'two');
    }

    function testSameDeadlinesWithPriorities() {
        $this->task->givenTheTask_In('a', 'root');
        $this->task->givenTheTask_In('b', 'root');
        $this->task->givenTheTask_In('aa', 'a');
        $this->task->givenTheTask_In('ab', 'a');
        $this->task->givenTheTask_In('ba', 'b');
        $this->task->givenTheTask_In('bb', 'b');

        $this->task->given_HasThePriority('b', 1);
        $this->task->given_HasThePriority('a', 2);
        $this->task->given_HasThePriority('ab', 5);

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(4);
        $this->thenSlot_ShouldBeTask(1, 'ba');
        $this->thenSlot_ShouldBeTask(2, 'bb');
        $this->thenSlot_ShouldBeTask(3, 'ab');
        $this->thenSlot_ShouldBeTask(4, 'aa');
    }

    function testSameDeadlinesNoPriorities() {
        $this->task->givenTheTask_In('a', 'root');
        $this->task->givenTheTask_In('b', 'root');
        $this->task->givenTheTask_In('one', 'a');
        $this->task->givenTheTask_In('two', 'a');
        $this->task->givenTheTask_In('three', 'b');
        $this->task->givenTheTask_In('four', 'b');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(4);
        $this->thenSlot_ShouldBeTask(1, 'one');
        $this->thenSlot_ShouldBeTask(2, 'two');
        $this->thenSlot_ShouldBeTask(3, 'four');
        $this->thenSlot_ShouldBeTask(4, 'three');
    }

    function testCompletedTask() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->givenTheTask_In('two', 'root');
        $this->task->given_IsDone('one');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(1);
        $this->thenSlot_ShouldBeTask(1, 'two');
    }

    function testCascadingCompleteness() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->givenTheTask_In('two', 'root');
        $this->task->givenTheTask_In('one_one', 'one');
        $this->task->given_IsDone('one');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(1);
        $this->thenSlot_ShouldBeTask(1, 'two');
    }

    function testTaskTree() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->givenTheTask_In('two', 'root');
        $this->task->givenTheTask_In('one_one', 'one');
        $this->task->givenTheTask_In('leaf_one', 'one');
        $this->task->givenTheTask_In('leaf_two', 'one_one');
        $this->task->givenTheTask_In('leaf_three', 'two');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBeTask(1, 'leaf_one');
        $this->thenSlot_ShouldBeTask(2, 'leaf_two');
        $this->thenSlot_ShouldBeTask(3, 'leaf_three');
    }

    function testTreeWithDoneTasks() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->givenTheTask_In('two', 'root');
        $this->task->givenTheTask_In('one_one', 'one');
        $this->task->givenTheTask_In('leaf_one', 'one');
        $this->task->givenTheTask_In('leaf_two', 'one_one');
        $this->task->givenTheTask_In('leaf_three', 'two');

        $this->task->given_IsDone('leaf_two');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBeTask(1, 'leaf_one');
        $this->thenSlot_ShouldBeTask(2, 'one_one');
        $this->thenSlot_ShouldBeTask(3, 'leaf_three');
    }

    function testTaskWithoutDuration() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->givenTheTask_In('two', 'root');
        $this->task->givenTheTask_In('three', 'root');
        $this->task->given_HasTheDeadline('three', 'today');
        $this->task->given_Takes_Minutes('one', 0);
        $this->task->given_Takes_Minutes('two', 1);
        $this->task->givenIHaveLogged_MinutesFor(2, 'two');

        $this->whenICreateTheSchedule();
        $this->thenThereShouldBe_SlotsInTheSchedule(2);
        $this->thenSlot_ShouldBeTask(1, 'three');
        $this->thenSlot_ShouldBeTask(2, 'two');
    }

    function testTaskWithMinimalDuration() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->given_Takes_Minutes('one', 1);

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(1);
        $this->thenSlot_ShouldBe_Minutes(1, 1);
    }

    function testTaskWithLargerDuration() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->given_Takes_Minutes('one', 11);

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(1);
        $this->thenSlot_ShouldBe_Minutes(1, 11);
    }

    function testInheritDeadline() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->givenTheTask_In('one_one', 'one');
        $this->task->givenTheTask_In('one_two', 'one');
        $this->task->givenTheTask_In('two', 'root');

        $this->task->given_HasTheDeadline('one', 'today');
        $this->task->given_HasTheDeadline('one_two', 'next week');
        $this->task->given_HasTheDeadline('two', 'tomorrow');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBeTask(1, 'one_one');
        $this->thenSlot_ShouldBeTask(2, 'two');
        $this->thenSlot_ShouldBeTask(3, 'one_two');
    }

    function testLoggedTime() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->given_Takes_Minutes('one', 12);
        $this->task->givenIHaveLogged_MinutesFor(5, 'one');

        $this->whenICreateTheSchedule();

        $this->thenSlot_ShouldBe_Minutes(1, 7);
    }

    function testTimeWindowConstraint() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->given_Takes_Minutes('one', 12);
        $this->task->given_HasAWindowFrom_Until('one', 'now', '5 minutes');
        $this->task->given_HasAWindowFrom_Until('one', '7 minutes', '17 minutes');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(2);
        $this->thenSlot_ShouldBe_Minutes(1, 5);
        $this->thenSlot_ShouldBe_Minutes(2, 7);
    }

    function testCascadingTimeWindows() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->given_Takes_Minutes('one', 12);
        $this->task->given_HasAWindowFrom_Until('root', '2 minutes', '20 minutes');
        $this->task->given_HasAWindowFrom_Until('one', 'now', '5 minutes');
        $this->task->given_HasAWindowFrom_Until('one', '7 minutes', '14 minutes');
        $this->task->given_HasAWindowFrom_Until('one', '15 minutes', '20 minutes');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBe_Minutes(1, 3);
        $this->thenSlot_ShouldBe_Minutes(2, 7);
        $this->thenSlot_ShouldBe_Minutes(3, 2);
    }

    function testWindowWithQuota() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->given_Takes_Minutes('one', 10);
        $this->task->given_HasAWindowFrom_Until_WithAQuotaOf_Minutes('one', 'now', '5 minutes', 2);
        $this->task->given_HasAWindowFrom_Until_WithAQuotaOf_Minutes('one', '7 minutes', '17 minutes', 5);
        $this->task->given_HasAWindowFrom_Until_WithAQuotaOf_Minutes('one', '20 minutes', '25 minutes', 5);

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBe_Minutes(1, 2);
        $this->thenSlot_ShouldBe_Minutes(2, 5);
        $this->thenSlot_ShouldBe_Minutes(3, 3);
    }

    function testDependencies() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->givenTheTask_In('two', 'root');
        $this->task->givenTheTask_In('three', 'root');

        $this->task->given_HasTheDeadline('one', 'today');
        $this->task->given_HasTheDeadline('two', 'tomorrow');
        $this->task->given_HasTheDeadline('three', 'next week');

        $this->task->given_DependsOn('one', 'two');
        $this->task->given_DependsOn('two', 'three');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBeTask(1, 'three');
        $this->thenSlot_ShouldBeTask(2, 'two');
        $this->thenSlot_ShouldBeTask(3, 'one');
    }

    function testRepeatingTask() {
        $this->task->givenTheRepeatingTask_In('one', 'root');
        $this->task->given_IsRepeatedEach_Minutes('one', 30);
        $this->task->given_Takes_Minutes('one', 5);
        $this->task->given_HasAWindowFrom_Until('one', 'now', '2 minutes');
        $this->task->given_HasAWindowFrom_Until('one', '4 minutes', '8 minutes');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(8);
        $this->thenSlot_ShouldStart(1, 'now');
        $this->thenSlot_ShouldBe_Minutes(1, 2);
        $this->thenSlot_ShouldBe_Minutes(2, 3);
        $this->thenSlot_ShouldStart(3, '30 minutes');
        $this->thenSlot_ShouldBe_Minutes(3, 2);
        $this->thenSlot_ShouldStart(4, '34 minutes');
        $this->thenSlot_ShouldBe_Minutes(4, 3);
    }

    function testRepeatingExecutionWindow() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->given_Takes_Minutes('one', '10');
        $this->task->given_HasAWindowFrom_Until('one', 'now', '2 minutes');
        $this->task->givenTheWindowsOf_AreRepeatedEvery_Minutes('one', 15);
        $this->task->given_HasAWindowFrom_Until('one', '4 minutes', '6 minutes');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(5);
        $this->thenSlot_ShouldStart(1, 'now');
        $this->thenSlot_ShouldStart(2, '4 minutes');
        $this->thenSlot_ShouldStart(3, '15 minutes');
        $this->thenSlot_ShouldStart(4, '19 minutes');
        $this->thenSlot_ShouldStart(5, '30 minutes');
    }

    function testRepeatingTaskWithRepeatingWindows() {
        $this->task->givenTheRepeatingTask_In('one', 'root');
        $this->whenITryToRepeatTheWindowsOf('one');
        $this->thenAnExceptionShouldBeThrown();
    }

    ######################### SETUP ############################

    protected function setUp() {
        parent::setUp();
        $this->background();
    }

    private function whenICreateTheSchedule() {
        $scheduler = new Scheduler($this->task->root);
        $this->schedule = $scheduler->createSchedule(new \DateTime(), $this->aligned('2 hours'));
    }

    private function thenThereShouldBe_SlotsInTheSchedule($count) {
        $this->assertCount($count, $this->schedule->slots);
    }

    private function thenSlot_ShouldBeTask($index, $name) {
        $this->assertEquals($name, $this->schedule->slots[$index - 1]->task->getName());
    }

    private function thenSlot_ShouldBe_Minutes($index, $minutes) {
        $this->assertEquals($minutes, $this->schedule->slots[$index - 1]->window->getSeconds() / 60);
    }

    private function thenSlot_ShouldStart($index, $when) {
        $this->assertEquals($this->aligned($when), $this->schedule->slots[$index - 1]->window->start);
    }

    private function aligned($from) {
        return new \DateTime(date('Y-m-d H:i:0', strtotime($from)));
    }

    private function whenITryToRepeatTheWindowsOf($task) {
        try {
            $this->task->givenTheWindowsOf_AreRepeatedEvery_Minutes($task, 1);
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    private function thenAnExceptionShouldBeThrown() {
        $this->assertNotNull($this->caught);
    }

}