<?php
namespace spec\rtens\xkdl;

use PHPUnit_Framework_TestCase;
use rtens\xkdl\lib\ExecutionWindow;
use rtens\xkdl\lib\TimeWindow;
use rtens\xkdl\RepeatingTask;
use rtens\xkdl\Scheduler;
use rtens\xkdl\lib\Slot;
use rtens\xkdl\Task;

/**
 * @property Task root
 * @property array|Task[]|RepeatingTask[] tasks
 * @property array|Slot[] schedule
 * @property \Exception|null caught
 */
class SchedulingTest extends PHPUnit_Framework_TestCase {

    public function background() {
        $this->givenTheRootTask('root');
    }

    function testEmptyRoot() {
        $this->whenICreateTheSchedule();
        $this->thenThereShouldBe_SlotsInTheSchedule(0);
    }

    function testSingleTask() {
        $this->givenTheTask_In('one', 'root');
        $this->whenICreateTheSchedule();
        $this->thenThereShouldBe_SlotsInTheSchedule(1);
        $this->thenSlot_ShouldBeTask(1, 'one');
    }

    function testEarliestDeadlineFirst() {
        $this->givenTheTask_In('one', 'root');
        $this->givenTheTask_In('two', 'root');
        $this->givenTheTask_In('three', 'root');
        $this->given_HasTheDeadline('one', 'tomorrow');
        $this->given_HasTheDeadline('two', 'next week');
        $this->given_HasTheDeadline('three', 'today');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBeTask(1, 'three');
        $this->thenSlot_ShouldBeTask(2, 'one');
        $this->thenSlot_ShouldBeTask(3, 'two');
    }

    function testNoDeadline() {
        $this->givenTheTask_In('one', 'root');
        $this->givenTheTask_In('two', 'root');
        $this->givenTheTask_In('three', 'root');
        $this->given_HasTheDeadline('one', 'tomorrow');
        $this->given_HasTheDeadline('three', 'next week');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBeTask(1, 'one');
        $this->thenSlot_ShouldBeTask(2, 'three');
        $this->thenSlot_ShouldBeTask(3, 'two');
    }

    function testCompletedTask() {
        $this->givenTheTask_In('one', 'root');
        $this->givenTheTask_In('two', 'root');
        $this->given_IsDone('one');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(1);
        $this->thenSlot_ShouldBeTask(1, 'two');
    }

    function testCascadingCompleteness() {
        $this->givenTheTask_In('one', 'root');
        $this->givenTheTask_In('two', 'root');
        $this->givenTheTask_In('one_one', 'one');
        $this->given_IsDone('one');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(1);
        $this->thenSlot_ShouldBeTask(1, 'two');
    }

    function testTaskTree() {
        $this->givenTheTask_In('one', 'root');
        $this->givenTheTask_In('two', 'root');
        $this->givenTheTask_In('one_one', 'one');
        $this->givenTheTask_In('leaf_one', 'one');
        $this->givenTheTask_In('leaf_two', 'one_one');
        $this->givenTheTask_In('leaf_three', 'two');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBeTask(1, 'leaf_two');
        $this->thenSlot_ShouldBeTask(2, 'leaf_one');
        $this->thenSlot_ShouldBeTask(3, 'leaf_three');
    }

    function testTreeWithDoneTasks() {
        $this->givenTheTask_In('one', 'root');
        $this->givenTheTask_In('two', 'root');
        $this->givenTheTask_In('one_one', 'one');
        $this->givenTheTask_In('leaf_one', 'one');
        $this->givenTheTask_In('leaf_two', 'one_one');
        $this->givenTheTask_In('leaf_three', 'two');

        $this->given_IsDone('leaf_two');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBeTask(1, 'one_one');
        $this->thenSlot_ShouldBeTask(2, 'leaf_one');
        $this->thenSlot_ShouldBeTask(3, 'leaf_three');
    }

    function testTaskWithoutDuration() {
        $this->givenTheTask_In('one', 'root');
        $this->givenTheTask_In('two', 'root');
        $this->givenTheTask_In('three', 'root');
        $this->given_HasTheDeadline('three', 'today');
        $this->given_Takes_Minutes('one', 0);
        $this->given_Takes_Minutes('two', 1);
        $this->givenIHaveLogged_MinutesFor(2, 'two');

        $this->whenICreateTheSchedule();
        $this->thenThereShouldBe_SlotsInTheSchedule(2);
        $this->thenSlot_ShouldBeTask(1, 'three');
        $this->thenSlot_ShouldBeTask(2, 'two');
    }

    function testTaskWithMinimalDuration() {
        $this->givenTheTask_In('one', 'root');
        $this->given_Takes_Minutes('one', 1);

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(1);
        $this->thenSlot_ShouldBe_Minutes(1, 1);
    }

    function testTaskWithLargerDuration() {
        $this->givenTheTask_In('one', 'root');
        $this->given_Takes_Minutes('one', 11);

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(1);
        $this->thenSlot_ShouldBe_Minutes(1, 11);
    }

    function testInheritDeadline() {
        $this->givenTheTask_In('one', 'root');
        $this->givenTheTask_In('one_one', 'one');
        $this->givenTheTask_In('one_two', 'one');
        $this->givenTheTask_In('two', 'root');

        $this->given_HasTheDeadline('one', 'today');
        $this->given_HasTheDeadline('one_two', 'next week');
        $this->given_HasTheDeadline('two', 'tomorrow');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBeTask(1, 'one_one');
        $this->thenSlot_ShouldBeTask(2, 'two');
        $this->thenSlot_ShouldBeTask(3, 'one_two');
    }

    function testLoggedTime() {
        $this->givenTheTask_In('one', 'root');
        $this->given_Takes_Minutes('one', 12);
        $this->givenIHaveLogged_MinutesFor(5, 'one');

        $this->whenICreateTheSchedule();

        $this->thenSlot_ShouldBe_Minutes(1, 7);
    }

    function testTimeWindowConstraint() {
        $this->givenTheTask_In('one', 'root');
        $this->given_Takes_Minutes('one', 12);
        $this->given_HasAWindowFrom_Until('one', 'now', '5 minutes');
        $this->given_HasAWindowFrom_Until('one', '7 minutes', '17 minutes');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(2);
        $this->thenSlot_ShouldBe_Minutes(1, 5);
        $this->thenSlot_ShouldBe_Minutes(2, 7);
    }

    function testCascadingTimeWindows() {
        $this->givenTheTask_In('one', 'root');
        $this->given_Takes_Minutes('one', 12);
        $this->given_HasAWindowFrom_Until('root', '2 minutes', '20 minutes');
        $this->given_HasAWindowFrom_Until('one', 'now', '5 minutes');
        $this->given_HasAWindowFrom_Until('one', '7 minutes', '14 minutes');
        $this->given_HasAWindowFrom_Until('one', '15 minutes', '20 minutes');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBe_Minutes(1, 3);
        $this->thenSlot_ShouldBe_Minutes(2, 7);
        $this->thenSlot_ShouldBe_Minutes(3, 2);
    }

    function testWindowWithQuota() {
        $this->givenTheTask_In('one', 'root');
        $this->given_Takes_Minutes('one', 10);
        $this->given_HasAWindowFrom_Until_WithAQuotaOf_Minutes('one', 'now', '5 minutes', 2);
        $this->given_HasAWindowFrom_Until_WithAQuotaOf_Minutes('one', '7 minutes', '17 minutes', 5);
        $this->given_HasAWindowFrom_Until_WithAQuotaOf_Minutes('one', '20 minutes', '25 minutes', 5);

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBe_Minutes(1, 2);
        $this->thenSlot_ShouldBe_Minutes(2, 5);
        $this->thenSlot_ShouldBe_Minutes(3, 3);
    }

    function testDependencies() {
        $this->givenTheTask_In('one', 'root');
        $this->givenTheTask_In('two', 'root');
        $this->givenTheTask_In('three', 'root');

        $this->given_HasTheDeadline('one', 'today');
        $this->given_HasTheDeadline('two', 'tomorrow');
        $this->given_HasTheDeadline('three', 'next week');

        $this->given_DependsOn('one', 'two');
        $this->given_DependsOn('two', 'three');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(3);
        $this->thenSlot_ShouldBeTask(1, 'three');
        $this->thenSlot_ShouldBeTask(2, 'two');
        $this->thenSlot_ShouldBeTask(3, 'one');
    }

    function testRepeatingTask() {
        $this->givenTheRepeatingTask_In('one', 'root');
        $this->given_IsRepeatedEach_Minutes('one', 30);
        $this->given_Takes_Minutes('one', 5);
        $this->given_HasAWindowFrom_Until('one', 'now', '2 minutes');
        $this->given_HasAWindowFrom_Until('one', '4 minutes', '8 minutes');

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
        $this->givenTheTask_In('one', 'root');
        $this->given_Takes_Minutes('one', '10');
        $this->given_HasAWindowFrom_Until('one', 'now', '2 minutes');
        $this->givenTheWindowsOf_AreRepeatedEvery_Minutes('one', 15);
        $this->given_HasAWindowFrom_Until('one', '4 minutes', '6 minutes');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(5);
        $this->thenSlot_ShouldStart(1, 'now');
        $this->thenSlot_ShouldStart(2, '4 minutes');
        $this->thenSlot_ShouldStart(3, '15 minutes');
        $this->thenSlot_ShouldStart(4, '19 minutes');
        $this->thenSlot_ShouldStart(5, '30 minutes');
    }

    function testRepeatingTaskWithRepeatingWindows() {
        $this->givenTheRepeatingTask_In('one', 'root');
        $this->whenITryToRepeatTheWindowsOf('one');
        $this->thenAnExceptionShouldBeThrown();
    }

    ######################### SETUP ############################

    protected function setUp() {
        parent::setUp();
        $this->background();
    }

    private function givenTheRootTask($name) {
        $this->root = new Task($name, 0);
        $this->tasks[$name] = $this->root;
    }

    private function givenTheTask_In($child, $parent) {
        $this->tasks[$child] = new Task($child, 1 / 60);
        $this->tasks[$parent]->addChild($this->tasks[$child]);
    }

    private function givenTheRepeatingTask_In($task, $parent) {
        $this->tasks[$task] = new RepeatingTask($task, 1 / 60);
        $this->tasks[$parent]->addChild($this->tasks[$task]);
    }

    private function whenICreateTheSchedule() {
        $scheduler = new Scheduler($this->root);
        $this->schedule = $scheduler->createSchedule(new \DateTime(), $this->aligned('2 hours'));
    }

    private function thenThereShouldBe_SlotsInTheSchedule($count) {
        $this->assertCount($count, $this->schedule);
    }

    private function thenSlot_ShouldBeTask($index, $name) {
        $this->assertEquals($name, $this->schedule[$index - 1]->task->getName());
    }

    private function given_HasTheDeadline($task, $deadline) {
        $this->tasks[$task]->setDeadline(new \DateTime($deadline));
    }

    private function given_Takes_Minutes($name, $minutes) {
        $this->tasks[$name]->setDuration($minutes / 60);
    }

    private function thenSlot_ShouldBe_Minutes($index, $minutes) {
        $this->assertEquals($minutes, $this->schedule[$index - 1]->window->getSeconds() / 60);
    }

    private function thenSlot_ShouldStart($index, $when) {
        $this->assertEquals($this->aligned($when), $this->schedule[$index - 1]->window->start);
    }

    private function givenIHaveLogged_MinutesFor($minutes, $name) {
        $this->tasks[$name]->addLog(new TimeWindow(new \DateTime(), new \DateTime($minutes . ' minutes')));
    }

    private function given_HasAWindowFrom_Until($task, $from, $until) {
        $this->tasks[$task]->addWindow(new ExecutionWindow($this->aligned($from), $this->aligned($until)));
    }

    private function given_HasAWindowFrom_Until_WithAQuotaOf_Minutes($task, $from, $until, $quota) {
        $this->tasks[$task]->addWindow(new ExecutionWindow($this->aligned($from), $this->aligned($until), $quota / 60));
    }

    private function aligned($from) {
        return new \DateTime(date('Y-m-d H:i:0', strtotime($from)));
    }

    private function given_DependsOn($task, $dependency) {
        $this->tasks[$task]->addDependency($this->tasks[$dependency]);
    }

    private function given_IsRepeatedEach_Minutes($task, $minutes) {
        $this->tasks[$task]->repeatEvery(new \DateInterval('PT' . $minutes . 'M'));
    }

    private function given_IsDone($task) {
        $this->tasks[$task]->setDone();
    }

    private function givenTheWindowsOf_AreRepeatedEvery_Minutes($task, $minutes) {
        $this->tasks[$task]->repeatWindow(new \DateInterval("PT{$minutes}M"));
    }

    private function whenITryToRepeatTheWindowsOf($task) {
        try {
            $this->givenTheWindowsOf_AreRepeatedEvery_Minutes($task, 1);
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    private function thenAnExceptionShouldBeThrown() {
        $this->assertNotNull($this->caught);
    }

}