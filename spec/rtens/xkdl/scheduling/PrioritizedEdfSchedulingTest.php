<?php
namespace spec\rtens\xkdl\scheduling;

use rtens\xkdl\Scheduler;
use rtens\xkdl\scheduler\PrioritizedEdfScheduler;
use rtens\xkdl\Task;

class PrioritizedEdfSchedulingTest extends SchedulingTest {

    protected function background() {
        $this->task->givenTheRootTask('root');
    }

    public function testSingleTask() {
        $this->task->givenTheTask_In('one', 'root');
        $this->whenICreateTheSchedule();
        $this->thenThereShouldBe_SlotsInTheSchedule(1);
        $this->thenSlot_ShouldBeTask(1, 'one');
    }

    public function testScheduleEarliestDeadlineFirst() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->givenTheTask_In('two', 'root');
        $this->task->givenTheTask_In('three', 'root');

        $this->task->given_HasTheDeadline('one', 'tomorrow');
        $this->task->given_HasTheDeadline('two', 'yesterday');
        $this->task->given_HasTheDeadline('three', 'now');

        $this->whenICreateTheSchedule();

        $this->thenSlot_ShouldBeTask(1, 'two');
        $this->thenSlot_ShouldBeTask(2, 'three');
        $this->thenSlot_ShouldBeTask(3, 'one');
    }

    public function testHighestPriorityFirst() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->givenTheTask_In('two', 'root');
        $this->task->givenTheTask_In('three', 'root');

        $this->task->given_HasThePriority('one', 3);
        $this->task->given_HasThePriority('two', 1);
        $this->task->given_HasThePriority('three', 2);

        $this->whenICreateTheSchedule();

        $this->thenSlot_ShouldBeTask(1, 'two');
        $this->thenSlot_ShouldBeTask(2, 'three');
        $this->thenSlot_ShouldBeTask(3, 'one');
    }

    public function testPrioritiesOverDeadlines() {
        $this->task->givenTheTask_In('one', 'root');
        $this->task->givenTheTask_In('two', 'root');
        $this->task->givenTheTask_In('three', 'root');
        $this->task->givenTheTask_In('four', 'root');

        $this->task->given_HasThePriority('one', 2);
        $this->task->given_HasThePriority('two', 1);
        $this->task->given_HasThePriority('three', 3);
        $this->task->given_HasThePriority('four', 2);

        $this->task->given_HasTheDeadline('one', 'tomorrow');
        $this->task->given_HasTheDeadline('two', 'yesterday');
        $this->task->given_HasTheDeadline('three', 'now');
        $this->task->given_HasTheDeadline('four', 'now');

        $this->whenICreateTheSchedule();

        $this->thenSlot_ShouldBeTask(1, 'two');
        $this->thenSlot_ShouldBeTask(2, 'four');
        $this->thenSlot_ShouldBeTask(3, 'one');
        $this->thenSlot_ShouldBeTask(4, 'three');
    }

    public function testCascadingPriorities() {
        $this->task->givenTheTask_In('a', 'root');
        $this->task->givenTheTask_In('b', 'root');
        $this->task->givenTheTask_In('c', 'root');
        $this->task->givenTheTask_In('aa', 'a');
        $this->task->givenTheTask_In('ab', 'a');
        $this->task->givenTheTask_In('ba', 'b');
        $this->task->givenTheTask_In('ca', 'c');

        $this->task->given_HasThePriority('a', 3);
        $this->task->given_HasThePriority('b', 1);
        $this->task->given_HasThePriority('c', 2);

        $this->task->given_HasTheDeadline('a', 'now');
        $this->task->given_HasTheDeadline('b', 'tomorrow');
        $this->task->given_HasTheDeadline('c', 'yesterday');
        $this->task->given_HasTheDeadline('aa', 'now');
        $this->task->given_HasTheDeadline('ab', 'yesterday');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(4);
        $this->thenSlot_ShouldBeTask(1, 'ba');
        $this->thenSlot_ShouldBeTask(2, 'ca');
        $this->thenSlot_ShouldBeTask(3, 'ab');
        $this->thenSlot_ShouldBeTask(4, 'aa');
    }

    public function testMultipleCascades() {
        $this->task->givenTheTask_In('a', 'root');
        $this->task->givenTheTask_In('b', 'root');
        $this->task->givenTheTask_In('aa', 'a');
        $this->task->givenTheTask_In('ab', 'a');
        $this->task->givenTheTask_In('ac', 'a');
        $this->task->givenTheTask_In('ad', 'a');
        $this->task->givenTheTask_In('ba', 'b');
        $this->task->givenTheTask_In('bb', 'b');
        $this->task->givenTheTask_In('aca', 'ac');
        $this->task->givenTheTask_In('acb', 'ac');
        $this->task->givenTheTask_In('aba', 'ab');

        $this->task->given_HasThePriority('a', 2);
        $this->task->given_HasThePriority('b', 1);
        $this->task->given_HasThePriority('aa', 2);
        $this->task->given_HasThePriority('ad', 1);
        $this->task->given_HasThePriority('bb', 4);

        $this->task->given_HasTheDeadline('aa', 'now');
        $this->task->given_HasTheDeadline('ab', 'yesterday');
        $this->task->given_HasTheDeadline('ac', 'now');
        $this->task->given_HasTheDeadline('ad', 'tomorrow');

        $this->whenICreateTheSchedule();

        $this->thenThereShouldBe_SlotsInTheSchedule(7);

        $this->thenSlot_ShouldBeTask(1, 'bb');
        $this->thenSlot_ShouldBeTask(2, 'ba');
        $this->thenSlot_ShouldBeTask(3, 'ad');
        $this->thenSlot_ShouldBeTask(4, 'aa');
        $this->thenSlot_ShouldBeTask(5, 'aba');
        $this->thenSlot_ShouldBeTask(6, 'aca');
        $this->thenSlot_ShouldBeTask(7, 'acb');
    }

    protected function createSchedulerInstance(Task $root) {
        return new PrioritizedEdfScheduler($root);
    }
}