<?php
namespace spec\rtens\xkdl;

use rtens\xkdl\lib\TimeWindow;
use rtens\xkdl\storage\TaskStore;
use rtens\xkdl\storage\Writer;
use rtens\xkdl\Task;
use spec\rtens\xkdl\fixtures\ConfigFixture;
use spec\rtens\xkdl\fixtures\FileFixture;
use spec\rtens\xkdl\fixtures\TaskFixture;
use spec\rtens\xkdl\fixtures\TimeFixture;
use watoki\scrut\Specification;

/**
 * @property TaskFixture task <-
 * @property ConfigFixture config <-
 * @property FileFixture file <-
 * @property TimeFixture time <-
 */
class StorageTest extends Specification {

    protected function background() {
        $this->time->givenTheTimeZoneIs('GMT0');
    }

    function testReadTask() {
        $this->config->givenTheDefaultDurationIs_Minutes(15);
        $this->file->givenTheFolder('root/__Task one');
        $this->file->givenTheFolder('root/Task two');
        $this->whenIReadTheTasks();
        $this->task->thenThereShouldBeATask('Task one');
        $this->task->then_ShouldTake_Minutes('Task one', 15);
        $this->task->thenThereShouldBeATask('Task two', 0);
    }

    function testDoneTask() {
        $this->file->givenTheFolder('root/__one');
        $this->file->givenTheFolder('root/X_two');
        $this->file->givenTheFolder('root/x_three');
        $this->file->givenTheFolder('root/x-men');

        $this->whenIReadTheTasks();

        $this->task->thenThereShouldBeATask('one');
        $this->task->then_ShouldBeDone('two');
        $this->task->then_ShouldBeDone('three');
        $this->task->then_ShouldBeOpen('x-men');
    }

    function testReadTree() {
        $this->file->givenTheFolder('root/one');
        $this->file->givenTheFolder('root/__two');
        $this->file->givenTheFolder('root/one/__one one');
        $this->file->givenTheFolder('root/one/__one two');
        $this->file->givenTheFolder('root/one/__one two/__one two one');

        $this->whenIReadTheTasks();

        $this->task->thenThereShouldBeATask('one');
        $this->task->thenThereShouldBeATask('two');
        $this->task->thenThereShouldBeATask('one/one one');
        $this->task->thenThereShouldBeATask('one/one two');
        $this->task->thenThereShouldBeATask('one/one two/one two one');
        $this->task->then_ShouldHaveNoChildren('two');
    }

    function testReadPriority() {
        $this->file->givenTheFolder('root/__1_Some task');
        $this->file->givenTheFolder('root/3_other task');
        $this->file->givenTheFolder('root/__this task');

        $this->whenIReadTheTasks();

        $this->task->then_ShouldHaveThePriority('Some task', 1);
        $this->task->then_ShouldHaveThePriority('other task', 3);
        $this->task->then_ShouldHaveThePriority('this task', Task::DEFAULT_PRIORITY);
    }

    function testReadPriorityFromFile() {
        $this->file->givenTheFolder('root/one');
        $this->file->givenTheFolder('root/two');
        $this->file->givenTheFile_WithContent('root/one/__.txt', 'priority: 73');

        $this->whenIReadTheTasks();

        $this->task->then_ShouldHaveThePriority('one', 73);
        $this->task->then_ShouldHaveThePriority('two', Task::DEFAULT_PRIORITY);
    }

    function testReadDuration() {
        $this->file->givenTheFolder('root/one');
        $this->file->givenTheFolder('root/two');
        $this->file->givenTheFile_WithContent('root/one/__.txt', 'duration: PT5H');
        $this->file->givenTheFile_WithContent('root/two/__.txt', 'duration: PT30M');
        $this->whenIReadTheTasks();
        $this->task->then_ShouldTake_Minutes('one', 300);
        $this->task->then_ShouldTake_Minutes('two', 30);
    }

    function testReadDeadline() {
        $this->file->givenTheFolder('root/__one');
        $this->file->givenTheFile_WithContent('root/__one/__.txt', 'deadline: 2014-12-31 12:00');
        $this->whenIReadTheTasks();
        $this->task->then_ShouldHaveTheDeadline('one', '2014-12-31 12:00');
        $this->task->then_ShouldHaveNoChildren('one');
    }

    function testReadRepeatingTask() {
        $this->file->givenTheFolder('root/__one');
        $this->file->givenTheFile_WithContent('root/__one/__.txt',
            "type: rtens\\xkdl\\task\\RepeatingTask\n" .
            "repeat: PT1H");
        $this->whenIReadTheTasks();
        $this->task->then_ShouldBeARepeatingTask('one');
        $this->task->thenTheRepetitionOf_ShouldBe('one', 'PT1H');
    }

    function testReadWindows() {
        $this->file->givenTheFolder('root/__one');
        $this->file->givenTheFile_WithContent('root/__one/windows.txt',
            "2014-01-01 12:00 >> 2014-01-01 13:00\n" .
            "2014-01-01 14:00 >> 2014-01-01 15:00");
        $this->whenIReadTheTasks();
        $this->task->then_ShouldHave_Windows('one', 2);
    }

    function testReadLogs() {
        $this->file->givenTheFolder('root/__one');
        $this->file->givenTheFile_WithContent('root/__one/logs.txt',
            "2014-01-01 12:00 >> 2014-01-01 13:00\n" .
            "2014-01-01 14:00 >> 2014-01-01 15:00");
        $this->whenIReadTheTasks();
        $this->task->then_ShouldHave_Logs('one', 2);
    }

    function testReadDescription() {
        $this->file->givenTheFolder('root/one');
        $this->file->givenTheFile_WithContent('root/one/description.txt', 'Some description');
        $this->whenIReadTheTasks();
        $this->task->thenThereShouldBeATask('one');
        $this->task->then_ShouldHaveTheDescription('one', 'Some description');
    }

    function testAddLogToNewTask() {
        $this->task->givenTheRootTask('root');
        $this->task->givenTheTask_In('one', 'root');
        $this->file->givenTheFolder('root');

        $this->whenIAddALogFrom_Until_To('2014-01-01 12:00', '2014-01-01 13:00', 'one');
        $this->file->thenThereShouldBeAFile_WithTheContent('root/one/logs.txt',
            "2014-01-01T12:00:00+00:00 >> 2014-01-01T13:00:00+00:00\n");
    }

    function testAddLogToExistingTask() {
        $this->task->givenTheRootTask('root');
        $this->task->givenTheTask_In('one', 'root');
        $this->task->givenTheTask_In('one/two', 'root');

        $this->file->givenTheFolder('root/one/two');
        $this->file->givenTheFile_WithContent('root/one/two/logs.txt', "now >> tomorrow\n");

        $this->whenIAddALogFrom_Until_To('2014-01-01 12:00', '2014-01-01 13:00', 'one/two');

        $this->file->thenThereShouldBeAFile_WithTheContent('root/one/two/logs.txt',
            "now >> tomorrow\n2014-01-01T12:00:00+00:00 >> 2014-01-01T13:00:00+00:00\n");
    }

    function testAddLogToExistingTaskWithStateAndDuration() {
        $this->task->givenTheRootTask('root');
        $this->task->givenTheTask_In('one', 'root');
        $this->task->givenTheTask_In('one/two', 'root');
        $this->task->givenTheTask_In('three', 'root');
        $this->task->givenTheTask_In('four', 'root');

        $this->file->givenTheFolder('root/__one/x_two');
        $this->file->givenTheFolder('root/__10_three');
        $this->file->givenTheFolder('root/x_2_four');

        $this->whenIAddALogFrom_Until_To('2014-01-01 12:00', '2014-01-01 13:00', 'one/two');
        $this->whenIAddALogFrom_Until_To('2014-01-01 12:00', '2014-01-01 13:00', 'three');
        $this->whenIAddALogFrom_Until_To('2014-01-01 12:00', '2014-01-01 13:00', 'four');

        $this->file->thenThereShouldBeAFile('root/__one/x_two/logs.txt');
        $this->file->thenThereShouldBeAFile('root/__10_three/logs.txt');
        $this->file->thenThereShouldBeAFile('root/x_2_four/logs.txt');
    }

    ###################### SETUP ########################

    private function whenIReadTheTasks() {
        /** @var TaskStore $store */
        $store = $this->factory->getInstance(TaskStore::$CLASS);
        $this->task->root = $store->getRoot();
    }

    public function whenIAddALogFrom_Until_To($start, $end, $task) {
        $writer = new Writer();
        $writer->config = $this->config->getConfig();
        $writer->addLog($task, new TimeWindow(new \DateTime($start), new \DateTime($end)));
    }

} 