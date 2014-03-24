<?php
namespace spec\rtens\xkdl;

use rtens\xkdl\lib\TimeSpan;
use rtens\xkdl\storage\Reader;
use rtens\xkdl\storage\Writer;
use rtens\xkdl\web\Presenter;
use rtens\xkdl\web\root\ScheduleResource;
use watoki\curir\http\Url;

/**
 * @property string fieldTask
 * @property ScheduleResource resource
 * @property \DateTime|null fieldStart
 * @property \DateTime|null fieldEnd
 * @property \Exception|null caught
 * @property Presenter presenter
 */
class ScheduleResourceTest extends \PHPUnit_Framework_TestCase {

    function testStartLogging() {
        $this->givenIHaveEnteredTheTask('/some/task');
        $this->givenIHaveEnteredTheStartTime('2001-01-01 12:00');
        $this->whenIStartLogging();
        $this->thenLoggingShouldBeStarted_At('/some/task', '2001-01-01 12:00');
    }

    function testDontStartLoggingIfAlreadyLogging() {
        $this->givenALogHasBeenStartedFor_At('/some/task', '2011-11-11 11:11');
        $this->givenIHaveEnteredTheTask('/my/task');
        $this->givenIHaveEnteredTheStartTime('tomorrow');
        $this->whenITryToStartLogging();
        $this->thenAnExceptionShouldBeThrown();
    }

    function testFinishLoggingForNewTask() {
        $this->givenTheFolder('root');
        $this->givenALogHasBeenStartedFor_At('/this/new/task', '2011-11-11 11:11');
        $this->givenIHaveEnteredTheEndTime('2011-11-11 11:12');
        $this->whenIFinishLogging();
        $this->thenNoLoggingShouldBeGoingOn();
        $this->thenThereShouldBeAFile_WithTheContent('root/this/new/task/logs.txt',
            "2011-11-11T11:11:00+01:00 >> 2011-11-11T11:12:00+01:00\n");
    }

    function testCancelLogging() {
        $this->givenTheFolder('root');
        $this->givenALogHasBeenStartedFor_At('/this/new/task', '2011-11-11 11:11');
        $this->givenIHaveEnteredTheEndTime('2011-11-11 11:12');
        $this->whenICancelLogging();
        $this->thenNoLoggingShouldBeGoingOn();
        $this->thenThereShouldBeNoFile('root/this/new/task/logs.txt');
    }

    function testAddLog() {
        $this->givenTheFolder('root/some/task');
        $this->givenTheFile_WithContent('root/some/task/logs.txt', "now >> then\n");
        $this->givenIHaveEnteredTheTask('/some/task');
        $this->givenIHaveEnteredTheStartTime('2011-11-11 11:11');
        $this->givenIHaveEnteredTheEndTime('2011-11-11 11:12');
        $this->whenIStartLogging();
        $this->thenNoLoggingShouldBeGoingOn();
        $this->thenThereShouldBeAFile_WithTheContent('root/some/task/logs.txt',
            "now >> then\n2011-11-11T11:11:00+01:00 >> 2011-11-11T11:12:00+01:00\n");
    }

    function testAddLogToTasksWithMetaInformation() {
        $this->givenTheFolder('root/__meta/X_info/__1_task');
        $this->givenIHaveEnteredTheTask('/meta/info/task');
        $this->givenIHaveEnteredTheStartTime('2011-11-11 11:11');
        $this->givenIHaveEnteredTheEndTime('2011-11-11 11:12');
        $this->whenIStartLogging();
        $this->thenThereShouldBeAFile_WithTheContent('root/__meta/X_info/__1_task/logs.txt',
            "2011-11-11T11:11:00+01:00 >> 2011-11-11T11:12:00+01:00\n");
    }

    function testShowIdleLogger() {
        $this->givenTheFolder('root/first/task');
        $this->givenTheFolder('root/second/task');
        $this->givenTheFolder('root/done/X_task');
        $this->givenTheFolder('root/X_parent/done');
        $this->whenIGetTheSchedule();
        $this->thenTheActiveLoggerShould_BeShown('not');
        $this->thenTheIdleLoggerShould_BeShown('');
        $this->thenTheAutoCompleteListOfTasksShouldBe([
            "/done",
            "/first",
            "/second",
            "/first/task",
            "/second/task"
        ]);
    }

    function testShowActiveLogger() {
        $this->givenALogHasBeenStartedFor_At('/some/task', '2001-01-01 11:01:00');
        $this->whenIGetTheSchedule();
        $this->thenTheIdleLoggerShould_BeShown('not');
        $this->thenTheActiveLoggerShould_BeShown('');
        $this->thenTheActiveTaskShouldBe('/some/task');
        $this->thenTheStartTimeShouldBe('2001-01-01 11:01');
    }

    function testCreateSchedule() {
        $this->givenTheFolder('root/a/__aa');
        $this->givenTheFile_WithContent('root/a/__aa/__.txt', 'duration: PT3M');
        $this->givenTheFile_WithContent('root/a/__aa/logs.txt', "2001-01-01 9:00 >> 2001-01-01 9:01");
        $this->givenTheFolder('root/a/__ab');
        $this->givenTheFile_WithContent('root/a/__ab/__.txt', 'deadline: 1 day 2 hours 5 minutes');
        $this->givenTheFile_WithContent('root/a/__ab/logs.txt', "2001-01-01 9:00 >> 2001-01-01 9:03");
        $this->givenTheFolder('root/b/ab/__abc');

        $this->givenTheFile_WithContent('schedule.txt',
            "2001-01-01T12:00:00+01:00 >> 2001-01-01T12:10:00+01:00\n" .
            "2001-01-01T12:00:00+01:00 >> 2001-01-01T12:01:00+01:00 >> /a/ab\n" .
            "2001-01-01T12:01:00+01:00 >> 2001-01-01T12:03:00+01:00 >> /a/aa\n" .
            "2001-01-01T12:03:00+01:00 >> 2001-01-01T12:04:00+01:00 >> /b/ab/abc\n" .
            "");

        $this->whenIGetTheSchedule();

        $this->thenThereShouldBe_Slots(3);

        $this->thenTheNameOfSlot_ShouldBe(1, 'ab');
        $this->thenTheStartOfSlot_ShouldBe(1, '12:00');
        $this->thenTheEndOfSlot_ShouldBe(1, '12:01');
        $this->thenTheDeadlineOfSlot_ShouldBe(1, '1d 2h 5m');
        $this->thenTheDurationOfSlot_ShouldBe_With_Completed(1, '0.05 / 0.02', '100%');

        $this->thenTheNameOfSlot_ShouldBe(2, 'aa');
        $this->thenTheStartOfSlot_ShouldBe(2, '12:01');
        $this->thenTheEndOfSlot_ShouldBe(2, '12:03');
        $this->thenSlot_ShouldHaveNoDeadline(2);
        $this->thenTheDurationOfSlot_ShouldBe_With_Completed(2, '0.02 / 0.05', '33.33%');

        $this->thenParentOfSlot_ShouldBe(3, '/b/ab');
    }

    function testWriteScheduleCache() {
        $this->givenTheFolder('root/a/__aa');
        $this->givenTheFile_WithContent('root/a/__aa/__.txt', 'duration: PT3M');
        $this->givenTheFile_WithContent('root/a/__aa/logs.txt', "2001-01-01 9:00 >> 2001-01-01 9:01");
        $this->givenTheFolder('root/a/__ab');
        $this->givenTheFile_WithContent('root/a/__ab/__.txt', 'deadline: 1 day 2 hours 5 minutes');
        $this->givenTheFile_WithContent('root/a/__ab/logs.txt', "2001-01-01 9:00 >> 2001-01-01 9:03");
        $this->givenTheFolder('root/b/ab/__abc');

        $this->whenICreateANewScheduleFrom_Until("2001-01-01 12:00", "2001-01-01 12:10");

        $this->thenThereShouldBeAFile_WithTheContent('schedule.txt',
            "2001-01-01T12:00:00+01:00 >> 2001-01-01T12:10:00+01:00\n" .
            "2001-01-01T12:00:00+01:00 >> 2001-01-01T12:01:00+01:00 >> /a/ab\n" .
            "2001-01-01T12:01:00+01:00 >> 2001-01-01T12:03:00+01:00 >> /a/aa\n" .
            "2001-01-01T12:03:00+01:00 >> 2001-01-01T12:04:00+01:00 >> /b/ab/abc\n" .
            "");

        $this->thenThereShouldBeAFile_WithTheContent('schedules/2001-01-01_10-10-10.txt',
            "2001-01-01T12:00:00+01:00 >> 2001-01-01T12:10:00+01:00\n" .
            "2001-01-01T12:00:00+01:00 >> 2001-01-01T12:01:00+01:00 >> /a/ab\n" .
            "2001-01-01T12:01:00+01:00 >> 2001-01-01T12:03:00+01:00 >> /a/aa\n" .
            "2001-01-01T12:03:00+01:00 >> 2001-01-01T12:04:00+01:00 >> /b/ab/abc\n" .
            "");
    }

    function testEmptyCache() {
        $this->givenTheFolder('root/__a');
        $this->whenIGetTheSchedule();
        $this->thenThereShouldBe_Slots(0);
    }

    function testInvalidCache() {
        $this->givenTheFile_WithContent('schedule.txt', "now >> tomorrow >> not/existing/task");
        $this->whenIGetTheSchedule();
        $this->thenThereShouldBe_Slots(0);
    }

    ################ SETUP ####################

    protected function setUp() {
        parent::setUp();

        $config = new MockConfiguration(__DIR__);
        $config->defaultDuration = new TimeSpan('PT1M');
        @mkdir($config->userFolder(), 0777, true);

        $this->resource = new ScheduleResource(Url::parse('schedule'));
        $this->resource->writer = new Writer();
        $this->resource->writer->config = $config;
        $this->resource->reader = new Reader();
        $this->resource->reader->config = $config;

        $this->fieldEnd = null;
    }

    protected function tearDown() {
        $rm = function ($dir) use (&$rm) {
            foreach (glob($dir . '/*') as $file) {
                if (is_file($file)) {
                    @unlink($file);
                } else {
                    $rm($file);
                }
            }
            @rmdir($dir);
        };
        $rm($this->resource->writer->config->userFolder());
    }

    private function givenTheFolder($name) {
        @mkdir($this->resource->writer->config->userFolder() . '/' . $name, 0777, true);
    }

    private function givenIHaveEnteredTheTask($string) {
        $this->fieldTask = $string;
    }

    private function whenIStartLogging() {
        $this->resource->doLog($this->fieldTask, $this->fieldStart, $this->fieldEnd);
    }

    private function givenIHaveEnteredTheStartTime($string) {
        $this->fieldStart = new \DateTime($string);
    }

    private function thenLoggingShouldBeStarted_At($task, $start) {
        $info = $this->resource->writer->getOngoingLogInfo();
        $this->assertNotNull($info);
        $this->assertEquals($task, $info['task']);
        $this->assertEquals(date('c', strtotime($start)), $info['start']->format('c'));
    }

    private function givenALogHasBeenStartedFor_At($task, $start) {
        $this->resource->writer->startLogging($task, new \DateTime($start));
    }

    private function whenITryToStartLogging() {
        try {
            $this->whenIStartLogging();
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    private function thenAnExceptionShouldBeThrown() {
        $this->assertNotNull($this->caught);
    }

    private function thenThereShouldBeAFile_WithTheContent($path, $content) {
        $fullPath = $this->resource->writer->config->userFolder() . '/' . $path;
        $this->assertFileExists($fullPath);
        $this->assertEquals($content, file_get_contents($fullPath));
    }

    private function givenTheFile_WithContent($path, $content) {
        file_put_contents($this->resource->writer->config->userFolder() . '/' . $path, $content);
    }

    private function givenIHaveEnteredTheEndTime($string) {
        $this->fieldEnd = $string;
    }

    private function whenIFinishLogging() {
        $this->resource->doFinish(new \DateTime($this->fieldEnd));
    }

    private function thenNoLoggingShouldBeGoingOn() {
        $this->assertNull($this->resource->writer->getOngoingLogInfo());
    }

    private function whenICancelLogging() {
        $this->resource->doCancel();
    }

    private function thenThereShouldBeNoFile($path) {
        $this->assertFileNotExists($this->resource->writer->config->userFolder() . '/' . $path);
    }

    private function whenIGetTheSchedule() {
        $this->presenter = $this->resource->doGet();
    }

    private function thenTheActiveLoggerShould_BeShown($not) {
        $this->assertEquals(!!$not, !$this->presenter->getModel()['logging']);
    }

    private function thenTheIdleLoggerShould_BeShown($not) {
        $this->assertEquals(!!$not, !$this->presenter->getModel()['idle']);
    }

    private function thenTheAutoCompleteListOfTasksShouldBe($list) {
        $this->assertContains(json_encode($list), $this->presenter->getModel()['idle']['taskList']);
    }

    private function thenTheActiveTaskShouldBe($string) {
        $this->assertEquals($string, $this->presenter->getModel()['logging']['task']['value']);
    }

    private function thenTheStartTimeShouldBe($string) {
        $this->assertEquals($string, $this->presenter->getModel()['logging']['start']['value']);
    }

    private function thenThereShouldBe_Slots($int) {
        $this->assertCount($int, $this->presenter->getModel()['schedule']['slot']);
    }

    private function getSlot($position) {
        return $this->presenter->getModel()['schedule']['slot'][$position - 1];
    }

    private function thenTheStartOfSlot_ShouldBe($int, $string) {
        $this->assertEquals($string, $this->getSlot($int)['start']);
    }

    private function thenTheEndOfSlot_ShouldBe($int, $string) {
        $this->assertEquals($string, $this->getSlot($int)['end']);
    }

    private function thenTheNameOfSlot_ShouldBe($int, $string) {
        $this->assertEquals($string, $this->getSlot($int)['task']['name']);
    }

    private function thenSlot_ShouldHaveNoDeadline($int) {
        $this->assertNull($this->getSlot($int)['task']['deadline']);
    }

    private function thenTheDurationOfSlot_ShouldBe_With_Completed($int, $number, $percentage) {
        $this->assertEquals($number, $this->getSlot($int)['task']['duration']['number']);
        $this->assertContains($percentage, $this->getSlot($int)['task']['duration']['logged']['style']);
    }

    private function thenTheDeadlineOfSlot_ShouldBe($int, $string) {
        $this->assertEquals($string, $this->getSlot($int)['task']['deadline']['relative']);
    }

    private function thenParentOfSlot_ShouldBe($int, $string) {
        $this->assertEquals($string, $this->getSlot($int)['task']['parent']);
    }

    private function whenICreateANewScheduleFrom_Until($from, $until) {
        $this->resource->doPost(new \DateTime($from), new \DateTime($until));
    }

}