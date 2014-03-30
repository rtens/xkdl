<?php
namespace spec\rtens\xkdl;

use rtens\xkdl\web\Presenter;
use rtens\xkdl\web\root\ScheduleResource;
use spec\rtens\xkdl\fixtures\ConfigFixture;
use spec\rtens\xkdl\fixtures\FileFixture;
use watoki\curir\http\Url;
use watoki\scrut\Specification;

/**
 * @property string fieldTask
 * @property ScheduleResource resource
 * @property \DateTime|null fieldStart
 * @property \DateTime|null fieldEnd
 * @property \Exception|null caught
 * @property Presenter presenter
 *
 * @property ConfigFixture config <-
 * @property FileFixture file <-
 */
class ScheduleResourceTest extends Specification {

    function testStartLogging() {
        $this->givenIHaveEnteredTheTask('/some/task');
        $this->givenIHaveEnteredTheStartTime('2001-01-01 12:00');
        $this->whenIStartLogging();
        $this->thenLoggingShouldBeStarted_At('/some/task', '2001-01-01 12:00');
    }

    function testStartLogWhileLoggingOngoing() {
        $this->file->givenTheFolder('root');
        $this->givenALogHasBeenStartedFor_At('/some/task', '2011-11-11 11:11');
        $this->givenIHaveEnteredTheTask('/my/task');
        $this->givenIHaveEnteredTheStartTime('2011-11-11 12:12');
        $this->whenIStartLogging();
        $this->thenLoggingShouldBeStarted_At('/my/task', '2011-11-11 12:12');
        $this->file->thenThereShouldBeAFile_WithTheContent('root/some/task/logs.txt',
            "2011-11-11T11:11:00+01:00 >> 2011-11-11T12:12:00+01:00\n");
    }

    function testFinishLoggingForNewTask() {
        $this->file->givenTheFolder('root');
        $this->givenALogHasBeenStartedFor_At('/this/new/task', '2011-11-11 11:11');
        $this->givenIHaveEnteredTheEndTime('2011-11-11 11:12');
        $this->whenIFinishLogging();
        $this->thenNoLoggingShouldBeGoingOn();
        $this->file->thenThereShouldBeAFile_WithTheContent('root/this/new/task/logs.txt',
            "2011-11-11T11:11:00+01:00 >> 2011-11-11T11:12:00+01:00\n");
    }

    function testCancelLogging() {
        $this->file->givenTheFolder('root');
        $this->givenALogHasBeenStartedFor_At('/this/new/task', '2011-11-11 11:11');
        $this->givenIHaveEnteredTheEndTime('2011-11-11 11:12');
        $this->whenICancelLogging();
        $this->thenNoLoggingShouldBeGoingOn();
        $this->file->thenThereShouldBeNoFile('root/this/new/task/logs.txt');
    }

    function testAddLog() {
        $this->file->givenTheFolder('root/some/task');
        $this->file->givenTheFile_WithContent('root/some/task/logs.txt', "now >> then\n");
        $this->givenIHaveEnteredTheTask('/some/task');
        $this->givenIHaveEnteredTheStartTime('2011-11-11 11:11');
        $this->givenIHaveEnteredTheEndTime('2011-11-11 11:12');
        $this->whenIStartLogging();
        $this->thenNoLoggingShouldBeGoingOn();
        $this->file->thenThereShouldBeAFile_WithTheContent('root/some/task/logs.txt',
            "now >> then\n2011-11-11T11:11:00+01:00 >> 2011-11-11T11:12:00+01:00\n");
    }

    function testAddLogWhileOngoingLogging() {
        $this->givenALogHasBeenStartedFor_At('/some/other/task', 'yesterday');
        $this->file->givenTheFolder('root/some/task');
        $this->file->givenTheFile_WithContent('root/some/task/logs.txt', "now >> then\n");
        $this->givenIHaveEnteredTheTask('/some/task');
        $this->givenIHaveEnteredTheStartTime('2011-11-11 11:11');
        $this->givenIHaveEnteredTheEndTime('2011-11-11 11:12');
        $this->whenIStartLogging();
        $this->thenLoggingShouldBeStarted_At('/some/other/task', 'yesterday');
        $this->file->thenThereShouldBeAFile_WithTheContent('root/some/task/logs.txt',
            "now >> then\n2011-11-11T11:11:00+01:00 >> 2011-11-11T11:12:00+01:00\n");
    }

    function testAddLogToTasksWithMetaInformation() {
        $this->file->givenTheFolder('root/__meta/X_info/__1_task');
        $this->givenIHaveEnteredTheTask('/meta/info/task');
        $this->givenIHaveEnteredTheStartTime('2011-11-11 11:11');
        $this->givenIHaveEnteredTheEndTime('2011-11-11 11:12');
        $this->whenIStartLogging();
        $this->file->thenThereShouldBeAFile_WithTheContent('root/__meta/X_info/__1_task/logs.txt',
            "2011-11-11T11:11:00+01:00 >> 2011-11-11T11:12:00+01:00\n");
    }

    function testShowIdleLogger() {
        $this->file->givenTheFolder('root/first/task');
        $this->file->givenTheFolder('root/second/task');
        $this->file->givenTheFolder('root/done/X_task');
        $this->file->givenTheFolder('root/X_parent/done');
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
        $this->file->givenTheFolder('root/a/__aa');
        $this->file->givenTheFile_WithContent('root/a/__aa/__.txt', 'duration: PT3M');
        $this->file->givenTheFile_WithContent('root/a/__aa/logs.txt', "2001-01-01 9:00 >> 2001-01-01 9:01");
        $this->file->givenTheFolder('root/a/__ab');
        $this->file->givenTheFile_WithContent('root/a/__ab/__.txt', 'deadline: 2001-01-10 10:00');
        $this->file->givenTheFile_WithContent('root/a/__ab/logs.txt', "2001-01-01 9:00 >> 2001-01-01 9:03");
        $this->file->givenTheFolder('root/b/ab/__abc');

        $this->config->givenNowIs('2001-01-09 7:55');

        $this->file->givenTheFile_WithContent('schedule.txt',
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
        $this->thenTheActionTargetOfSlot_ShouldBe(1, '/a/ab');

        $this->thenTheNameOfSlot_ShouldBe(2, 'aa');
        $this->thenTheStartOfSlot_ShouldBe(2, '12:01');
        $this->thenTheEndOfSlot_ShouldBe(2, '12:03');
        $this->thenSlot_ShouldHaveNoDeadline(2);
        $this->thenTheDurationOfSlot_ShouldBe_With_Completed(2, '0.02 / 0.05', '33.33%');

        $this->thenParentOfSlot_ShouldBe(3, '/b/ab');
    }

    function testWriteScheduleCache() {
        $this->file->givenTheFolder('root/a/__aa');
        $this->file->givenTheFile_WithContent('root/a/__aa/__.txt', 'duration: PT3M');
        $this->file->givenTheFile_WithContent('root/a/__aa/logs.txt', "2001-01-01 9:00 >> 2001-01-01 9:01");
        $this->file->givenTheFolder('root/a/__ab');
        $this->file->givenTheFile_WithContent('root/a/__ab/__.txt', 'deadline: 1 day 2 hours 5 minutes');
        $this->file->givenTheFile_WithContent('root/a/__ab/logs.txt', "2001-01-01 9:00 >> 2001-01-01 9:03");
        $this->file->givenTheFolder('root/b/ab/__abc');

        $this->config->givenNowIs('20010101 10:10:10');

        $this->whenICreateANewScheduleFrom_Until("2001-01-01 12:00", "2001-01-01 12:10");

        $this->file->thenThereShouldBeAFile_WithTheContent('schedule.txt',
            "2001-01-01T12:00:00+01:00 >> 2001-01-01T12:10:00+01:00\n" .
            "2001-01-01T12:00:00+01:00 >> 2001-01-01T12:01:00+01:00 >> /a/ab\n" .
            "2001-01-01T12:01:00+01:00 >> 2001-01-01T12:03:00+01:00 >> /a/aa\n" .
            "2001-01-01T12:03:00+01:00 >> 2001-01-01T12:04:00+01:00 >> /b/ab/abc\n" .
            "");

        $this->file->thenThereShouldBeAFile_WithTheContent('schedules/20010101T101010.txt',
            "2001-01-01T12:00:00+01:00 >> 2001-01-01T12:10:00+01:00\n" .
            "2001-01-01T12:00:00+01:00 >> 2001-01-01T12:01:00+01:00 >> /a/ab\n" .
            "2001-01-01T12:01:00+01:00 >> 2001-01-01T12:03:00+01:00 >> /a/aa\n" .
            "2001-01-01T12:03:00+01:00 >> 2001-01-01T12:04:00+01:00 >> /b/ab/abc\n" .
            "");
    }

    function testEmptyCache() {
        $this->file->givenTheFolder('root/__a');
        $this->whenIGetTheSchedule();
        $this->thenThereShouldBe_Slots(0);
    }

    function testInvalidCache() {
        $this->file->givenTheFile_WithContent('schedule.txt', "now >> tomorrow >> not/existing/task");
        $this->whenIGetTheSchedule();
        $this->thenThereShouldBe_Slots(0);
    }

    function testMarkTaskDone() {
        $this->file->givenTheFolder('root/__a');
        $this->file->givenTheFolder('root/__a/b');

        $this->whenIMark_AsDone('/a');
        $this->whenIMark_AsDone('/a/b');
        $this->whenIMark_AsDone('/c');

        $this->file->thenThereShouldBeAFolder('root/X_a');
        $this->file->thenThereShouldBeAFolder('root/X_a/X_b');
        $this->file->thenThereShouldBeAFolder('root/X_c');
    }

    function testMarkDoneTaskAsDone() {
        $this->file->givenTheFolder('root/X_a');
        $this->whenIMark_AsDone('/a');
        $this->file->thenThereShouldBeAFolder('root/X_a');
    }

    function testMarkDoneTaskAsOpen() {
        $this->file->givenTheFolder('root/X_a');
        $this->whenIMark_AsOpen('/a');
        $this->file->thenThereShouldBeAFolder('root/__a');
    }

    ################ SETUP ####################

    protected function setUp() {
        parent::setUp();
        $this->resource = $this->factory->getInstance(ScheduleResource::CLASS, [Url::parse('schedule')]);

        $this->fieldEnd = null;

        $this->file->givenTheFolder('root');
        $this->config->givenTheDefaultDurationIs_Minutes(1);
    }

    private function givenIHaveEnteredTheTask($string) {
        $this->fieldTask = $string;
    }

    private function whenIStartLogging() {
        $this->resource->doStart($this->fieldTask, $this->fieldStart, $this->fieldEnd);
    }

    private function whenIMark_AsDone($task) {
        $this->resource->doDone($task);
    }

    private function whenIMark_AsOpen($task) {
        $this->resource->doOpen($task);
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

    private function givenIHaveEnteredTheEndTime($string) {
        $this->fieldEnd = $string;
    }

    private function whenIFinishLogging() {
        $this->resource->doStop(new \DateTime($this->fieldEnd));
    }

    private function thenNoLoggingShouldBeGoingOn() {
        $this->assertNull($this->resource->writer->getOngoingLogInfo());
    }

    private function whenICancelLogging() {
        $this->resource->doCancel();
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

    private function thenTheActionTargetOfSlot_ShouldBe($int, $string) {
        $this->assertEquals($string, $this->getSlot($int)['task']['target']['value']);
    }

}