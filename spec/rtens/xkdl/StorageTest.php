<?php
namespace spec\rtens\xkdl;

use rtens\xkdl\RepeatingTask;
use rtens\xkdl\storage\Reader;
use rtens\xkdl\Task;

/**
 * @property Task root
 */
class StorageTest extends \PHPUnit_Framework_TestCase {

    function testReadTask() {
        $this->givenTheFolder('root/__Task one');
        $this->whenIReadTasksFrom('root');
        $this->thenThereShouldBeATask('Task one');
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
    }

    function testReadDuration() {
        $this->givenTheFolder('root/__1.5_one');
        $this->whenIReadTasksFrom('root');
        $this->then_ShouldHaveTheDuration('one', 1.5);
    }

    function testDeadline() {
        $this->givenTheFolder('root/__one');
        $this->givenTheFile_WithContent('root/__one/__.txt', 'deadline: 2014-12-31 12:00');
        $this->whenIReadTasksFrom('root');
        $this->then_ShouldHaveTheDeadline('one', '2014-12-31 12:00');
    }

    function testRepeatingTask() {
        $this->givenTheFolder('root/__one');
        $this->givenTheFile_WithContent('root/__one/__.txt', 'repeat: PT1H');
        $this->whenIReadTasksFrom('root');
        $this->then_ShouldBeARepeatingTask('one');
        $this->thenTheRepetitionOf_ShouldBe('one', 'PT1H');
    }

    function testWindows() {
        $this->givenTheFolder('root/__one');
        $this->givenTheFile_WithContent('root/__one/windows.txt', "2014-01-01 12:00 >> 2014-01-01 13:00\n2014-01-01 14:00 >> 2014-01-01 15:00");
        $this->whenIReadTasksFrom('root');
        $this->then_ShouldHave_Windows('one', 2);
    }

    function testLogs() {
        $this->givenTheFolder('root/__one');
        $this->givenTheFile_WithContent('root/__one/logs.txt', "2014-01-01 12:00 >> 2014-01-01 13:00\n2014-01-01 14:00 >> 2014-01-01 15:00");
        $this->whenIReadTasksFrom('root');
        $this->then_ShouldHave_Logs('one', 2);
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

} 