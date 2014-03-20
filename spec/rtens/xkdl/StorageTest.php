<?php
namespace spec\rtens\xkdl;

use rtens\xkdl\lib\TimeWindow;
use rtens\xkdl\RepeatingTask;
use rtens\xkdl\storage\Reader;
use rtens\xkdl\storage\Writer;
use rtens\xkdl\Task;

/**
 * @property Task root
 * @property Task[] tasks
 */
class StorageTest extends \PHPUnit_Framework_TestCase {

    function testReadTask() {
        $this->givenTheFolder('root/__Task one');
        $this->givenTheFolder('root/Task two');
        $this->whenIReadTasksFrom('root');
        $this->thenThereShouldBeATask('Task one');
        $this->then_ShouldHaveTheDuration('Task one', Reader::MINIMUM_DURATION);
        $this->thenThereShouldBeATask('Task two', Reader::MINIMUM_DURATION);
    }

    function testCompletedTask() {
        $this->givenTheFolder('root/__one');
        $this->givenTheFolder('root/X_two');
        $this->whenIReadTasksFrom('root');
        $this->thenThereShouldBeATask('one');
        $this->then_ShouldBeDone('two');
    }

    function testReadTree() {
        $this->givenTheFolder('root/__one');
        $this->givenTheFolder('root/__two');
        $this->givenTheFolder('root/__one/__one one');
        $this->givenTheFolder('root/__one/__one two');
        $this->givenTheFolder('root/__one/__one two/__one two one');

        $this->whenIReadTasksFrom('root');

        $this->thenThereShouldBeATask('one');
        $this->thenThereShouldBeATask('two');
        $this->thenThereShouldBeATask('one/one one');
        $this->thenThereShouldBeATask('one/one two');
        $this->thenThereShouldBeATask('one/one two/one two one');
        $this->then_ShouldHaveNoChildren('two');
    }

    function testReadDuration() {
        $this->givenTheFolder('root/__1.5_one');
        $this->whenIReadTasksFrom('root');
        $this->then_ShouldHaveTheDuration('one', 1.5);
    }

    function testReadDeadline() {
        $this->givenTheFolder('root/__one');
        $this->givenTheFile_WithContent('root/__one/__.txt', 'deadline: 2014-12-31 12:00');
        $this->whenIReadTasksFrom('root');
        $this->then_ShouldHaveTheDeadline('one', '2014-12-31 12:00');
        $this->then_ShouldHaveNoChildren('one');
    }

    function testReadRepeatingTask() {
        $this->givenTheFolder('root/__one');
        $this->givenTheFile_WithContent('root/__one/__.txt', 'repeat: PT1H');
        $this->whenIReadTasksFrom('root');
        $this->then_ShouldBeARepeatingTask('one');
        $this->thenTheRepetitionOf_ShouldBe('one', 'PT1H');
    }

    function testReadWindows() {
        $this->givenTheFolder('root/__one');
        $this->givenTheFile_WithContent('root/__one/windows.txt', "2014-01-01 12:00 >> 2014-01-01 13:00\n2014-01-01 14:00 >> 2014-01-01 15:00");
        $this->whenIReadTasksFrom('root');
        $this->then_ShouldHave_Windows('one', 2);
    }

    function testReadLogs() {
        $this->givenTheFolder('root/__one');
        $this->givenTheFile_WithContent('root/__one/logs.txt', "2014-01-01 12:00 >> 2014-01-01 13:00\n2014-01-01 14:00 >> 2014-01-01 15:00");
        $this->whenIReadTasksFrom('root');
        $this->then_ShouldHave_Logs('one', 2);
    }

    function testAddLogToNewTask() {
        $this->givenTheRootTask('root');
        $this->givenTheTask_In('one', 'root');
        $this->givenTheFolder('root');

        $this->whenIAddALogFrom_Until_To('2014-01-01 12:00', '2014-01-01 13:00', 'one');
        $this->thenThereShouldBeAFile_WithTheContent('root/one/logs.txt',
            "2014-01-01T12:00:00+01:00 >> 2014-01-01T13:00:00+01:00\n");
    }

    function testAddLogToExistingTask() {
        $this->givenTheRootTask('root');
        $this->givenTheTask_In('one', 'root');
        $this->givenTheTask_In('one/two', 'root');

        $this->givenTheFolder('root/one/two');
        $this->givenTheFile_WithContent('root/one/two/logs.txt', "now >> tomorrow\n");

        $this->whenIAddALogFrom_Until_To('2014-01-01 12:00', '2014-01-01 13:00', 'one/two');

        $this->thenThereShouldBeAFile_WithTheContent('root/one/two/logs.txt',
            "now >> tomorrow\n2014-01-01T12:00:00+01:00 >> 2014-01-01T13:00:00+01:00\n");
    }

    function testAddLogToExistingTaskWithStateAndDuration() {
        $this->givenTheRootTask('root');
        $this->givenTheTask_In('one', 'root');
        $this->givenTheTask_In('one/two', 'root');
        $this->givenTheTask_In('three', 'root');
        $this->givenTheTask_In('four', 'root');

        $this->givenTheFolder('root/__one/X_two');
        $this->givenTheFolder('root/__10_three');
        $this->givenTheFolder('root/X_2_four');

        $this->whenIAddALogFrom_Until_To('2014-01-01 12:00', '2014-01-01 13:00', 'one/two');
        $this->whenIAddALogFrom_Until_To('2014-01-01 12:00', '2014-01-01 13:00', 'three');
        $this->whenIAddALogFrom_Until_To('2014-01-01 12:00', '2014-01-01 13:00', 'four');

        $this->thenThereShouldBeAFile('root/__one/X_two/logs.txt');
        $this->thenThereShouldBeAFile('root/__10_three/logs.txt');
        $this->thenThereShouldBeAFile('root/X_2_four/logs.txt');
    }

    ###################### SETUP ########################

    protected function tearDown() {
        $rm = function ($dir) use (&$rm) {
            foreach (glob($dir . '/*') as $file) {
                if (is_file($file)) {
                    unlink($file);
                } else {
                    $rm($file);
                }
            }
            rmdir($dir);
        };
        $rm(__DIR__ . '/__usr');
    }

    public function givenTheRootTask($name) {
        $this->root = new Task($name, 0);
        $this->tasks[$name] = $this->root;
    }

    public function givenTheTask_In($child, $parent) {
        $this->tasks[$child] = new Task($child, 1 / 60);
        $this->tasks[$parent]->addChild($this->tasks[$child]);
    }

    private function getTask($path, $task = null) {
        $task = $task ?: $this->root;
        foreach (explode('/', $path) as $name) {
            $task = $task->getChild($name);
        }
        return $task;
    }

    private function givenTheFolder($name) {
        @mkdir(__DIR__ . '/__usr/' . $name, 0777, true);
    }

    private function whenIReadTasksFrom($rootFolder) {
        $reader = new Reader(__DIR__ . '/__usr/' . $rootFolder);
        $this->root = $reader->read();
    }

    private function thenThereShouldBeATask($path) {
        $this->assertNotNull($this->getTask($path));
    }

    private function then_ShouldBeDone($path) {
        $this->assertTrue($this->getTask($path)->isDone());
    }

    private function then_ShouldHaveTheDuration($path, $duration) {
        $this->assertEquals($duration, $this->getTask($path)->getDuration());
    }

    private function givenTheFile_WithContent($path, $content) {
        file_put_contents(__DIR__ . '/__usr/' . $path, $content);
    }

    private function then_ShouldHaveTheDeadline($path, $deadline) {
        $this->assertEquals(new \DateTime($deadline), $this->getTask($path)->getDeadline());
    }

    private function then_ShouldBeARepeatingTask($path) {
        $this->assertTrue($this->getTask($path) instanceof RepeatingTask);
    }

    private function thenTheRepetitionOf_ShouldBe($path, $interval) {
        /** @var RepeatingTask $repeatingTask */
        $repeatingTask = $this->getTask($path);
        $this->assertEquals(new \DateInterval($interval), $repeatingTask->getRepetition());
    }

    private function then_ShouldHave_Windows($path, $count) {
        $this->assertCount($count, $this->getTask($path)->getWindows());
    }

    private function then_ShouldHave_Logs($path, $count) {
        $this->assertCount($count, $this->getTask($path)->getLogs());
    }

    private function then_ShouldHaveNoChildren($path) {
        $this->assertEmpty($this->getTask($path)->getChildren());
    }

    private function whenIAddALogFrom_Until_To($start, $end, $task) {
        $writer = new Writer(__DIR__ . '/__usr');
        $writer->addLog($task, new TimeWindow(new \DateTime($start), new \DateTime($end)));
    }

    private function thenThereShouldBeAFile_WithTheContent($path, $content) {
        $fullPath = __DIR__ . '/__usr/' . $path;
        $this->assertFileExists($fullPath);
        $this->assertEquals($content, file_get_contents($fullPath));
    }

    private function thenThereShouldBeAFile($path) {
        $fullPath = __DIR__ . '/__usr/' . $path;
        $this->assertFileExists($fullPath);
    }

} 