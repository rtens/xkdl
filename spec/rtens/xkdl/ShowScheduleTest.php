<?php
namespace spec\rtens\xkdl;

use rtens\xkdl\web\Presenter;
use rtens\xkdl\web\root\ScheduleResource;
use spec\rtens\xkdl\fixtures\ConfigFixture;
use spec\rtens\xkdl\fixtures\FileFixture;
use spec\rtens\xkdl\fixtures\TimeFixture;
use watoki\curir\http\Url;
use watoki\scrut\Specification;

/**
 * @property ScheduleResource resource
 * @property Presenter presenter
 *
 * @property ConfigFixture config <-
 * @property FileFixture file <-
 * @property TimeFixture time <-
 */
class ShowScheduleTest extends Specification {

    protected function background() {
        $this->time->givenTheTimeZoneIs('GMT0');
    }

    function testShowScheduleWthBunchOfTasks() {
        $this->file->givenTheFolder('root/a/__aa');
        $this->file->givenTheFile_WithContent('root/a/__aa/__.txt', 'duration: PT3M');
        $this->file->givenTheFile_WithContent('root/a/__aa/logs.txt', "2001-01-01 9:00 >> 2001-01-01 9:01");
        $this->file->givenTheFolder('root/a/__ab');
        $this->file->givenTheFile_WithContent('root/a/__ab/__.txt', 'deadline: 2001-01-10 10:00');
        $this->file->givenTheFile_WithContent('root/a/__ab/logs.txt', "2001-01-01 9:00 >> 2001-01-01 9:03");
        $this->file->givenTheFolder('root/b/ab/__abc');

        $this->file->givenTheFile_WithContent('schedule.txt',
            "2001-01-01T12:00:00+00:00 >> 2001-01-01T12:10:00+00:00\n" .
            "2001-01-01T12:00:00+00:00 >> 2001-01-01T12:01:00+00:00 >> /a/ab\n" .
            "2001-01-01T12:01:00+00:00 >> 2001-01-01T12:03:00+00:00 >> /a/aa\n" .
            "2001-01-01T12:03:00+00:00 >> 2001-01-01T12:04:00+00:00 >> /b/ab/abc\n" .
            "");

        $this->whenIGetTheSchedule();

        $this->thenThereShouldBe_Slots(3);

        $this->thenTheNameOfSlot_ShouldBe(1, 'ab');
        $this->thenTheStartOfSlot_ShouldBe(1, '12:00');
        $this->thenTheEndOfSlot_ShouldBe(1, '12:01');
        $this->thenTheDeadlineOfSlot_ShouldBe(1, '2001-01-10 10:00');
        $this->thenTheBufferOfSlot_ShouldBe(1, '8d 21h 59m');
        $this->thenTheDurationOfSlot_ShouldBe_With_Completed(1, '0.05 / 0.02', '100%');
        $this->thenTheActionTargetOfSlot_ShouldBe(1, '/a/ab');

        $this->thenTheNameOfSlot_ShouldBe(2, 'aa');
        $this->thenTheStartOfSlot_ShouldBe(2, '12:01');
        $this->thenTheEndOfSlot_ShouldBe(2, '12:03');
        $this->thenSlot_ShouldHaveNoDeadline(2);
        $this->thenTheDurationOfSlot_ShouldBe_With_Completed(2, '0.02 / 0.05', '33.33%');

        $this->thenParentOfSlot_ShouldBe(3, '/b/ab');
    }

    function testLateTask() {
        $this->file->givenTheFolder('root/a');
        $this->file->givenTheFile_WithContent('root/a/__.txt', 'deadline: 2001-01-01 12:01');
        $this->file->givenTheFolder('root/b');
        $this->file->givenTheFile_WithContent('root/b/__.txt', 'deadline: 2001-01-01 12:01');
        $this->file->givenTheFolder('root/x_done');
        $this->file->givenTheFile_WithContent('root/x_done/__.txt', 'deadline: 2001-01-01 12:01');
        $this->file->givenTheFolder('root/anytime');

        $this->file->givenTheFile_WithContent('schedule.txt',
            "2001-01-01T12:00:00+00:00 >> 2001-01-01T12:10:00+00:00\n" .
            "2001-01-01T12:00:00+00:00 >> 2001-01-01T12:01:00+00:00 >> /a\n" .
            "2001-01-01T12:01:00+00:00 >> 2001-01-01T12:02:00+00:00 >> /b\n" .
            "2001-01-01T12:02:00+00:00 >> 2001-01-01T12:03:00+00:00 >> /done\n" .
            "2001-01-01T12:03:00+00:00 >> 2001-01-01T12:04:00+00:00 >> /anytime\n" .
            "");

        $this->whenIGetTheSchedule();

        $this->thenTheBufferOfSlot_ShouldBe(1, '0d 0h 0m');
        $this->thenSlot_ShouldNotBeLate(1);

        $this->thenTheBufferOfSlot_ShouldBe(2, '0d 0h 1m');
        $this->thenSlot_ShouldBeLate(2);

        $this->thenSlot_ShouldHaveNoDeadline(3);
        $this->thenSlot_ShouldNotBeLate(3);

        $this->thenSlot_ShouldHaveNoDeadline(4);
        $this->thenSlot_ShouldNotBeLate(4);
    }

    function testCreateNewSchedule() {
        $this->file->givenTheFolder('root/a/__aa');
        $this->file->givenTheFile_WithContent('root/a/__aa/__.txt', 'duration: PT3M');
        $this->file->givenTheFile_WithContent('root/a/__aa/logs.txt', "2001-01-01 9:00 >> 2001-01-01 9:01");
        $this->file->givenTheFolder('root/a/__ab');
        $this->file->givenTheFile_WithContent('root/a/__ab/__.txt', 'deadline: 1 day 2 hours 5 minutes');
        $this->file->givenTheFile_WithContent('root/a/__ab/logs.txt', "2001-01-01 9:00 >> 2001-01-01 9:03");
        $this->file->givenTheFolder('root/b/ab/__abc');

        $this->config->givenNowIs('2001-01-01 10:10:10');

        $this->whenICreateANewScheduleFrom_Until("2001-01-01 12:00", "2001-01-01 12:10");

        $this->file->thenThereShouldBeAFile_WithTheContent('schedule.txt',
            "2001-01-01T12:00:00+00:00 >> 2001-01-01T12:10:00+00:00\n" .
            "2001-01-01T12:00:00+00:00 >> 2001-01-01T12:01:00+00:00 >> /a/ab\n" .
            "2001-01-01T12:01:00+00:00 >> 2001-01-01T12:03:00+00:00 >> /a/aa\n" .
            "2001-01-01T12:03:00+00:00 >> 2001-01-01T12:04:00+00:00 >> /b/ab/abc\n" .
            "");

        $this->file->thenThereShouldBeAFile_WithTheContent('schedules/20010101T101010.txt',
            "2001-01-01T12:00:00+00:00 >> 2001-01-01T12:10:00+00:00\n" .
            "2001-01-01T12:00:00+00:00 >> 2001-01-01T12:01:00+00:00 >> /a/ab\n" .
            "2001-01-01T12:01:00+00:00 >> 2001-01-01T12:03:00+00:00 >> /a/aa\n" .
            "2001-01-01T12:03:00+00:00 >> 2001-01-01T12:04:00+00:00 >> /b/ab/abc\n" .
            "");
    }

    function testEmptySchedule() {
        $this->file->givenTheFolder('root/__a');
        $this->whenIGetTheSchedule();
        $this->thenThereShouldBe_Slots(0);
    }

    function testInvalidScheduleFile() {
        $this->file->givenTheFolder('root/not');
        $this->file->givenTheFile_WithContent('schedule.txt',
            "2001-01-01T12:00:00+00:00 >> 2001-01-01T12:10:00+00:00\n" .
            "now >> tomorrow >> /not/existing/task");
        $this->whenIGetTheSchedule();
        $this->thenThereShouldBe_Slots(0);
    }

    function testMarkTaskDone() {
        $this->file->givenTheFolder('root/__a');
        $this->file->givenTheFolder('root/__a/b');

        $this->whenIMark_AsDone('/a');
        $this->whenIMark_AsDone('/a/b');
        $this->whenIMark_AsDone('/c');

        $this->file->thenThereShouldBeAFolder('root/x_a');
        $this->file->thenThereShouldBeAFolder('root/x_a/x_b');
        $this->file->thenThereShouldBeAFolder('root/x_c');
    }

    function testMarkDoneTaskAsDone() {
        $this->file->givenTheFolder('root/x_a');
        $this->whenIMark_AsDone('/a');
        $this->file->thenThereShouldBeAFolder('root/x_a');
    }

    function testMarkDoneTaskAsOpen() {
        $this->file->givenTheFolder('root/x_a');
        $this->whenIMark_AsOpen('/a');
        $this->file->thenThereShouldBeAFolder('root/__a');
    }

    ################ SETUP ####################

    protected function setUp() {
        parent::setUp();
        $this->resource = $this->factory->getInstance(ScheduleResource::$CLASS, [Url::parse('schedule')]);

        $this->file->givenTheFolder('root');
        $this->config->givenTheDefaultDurationIs_Minutes(1);
    }

    private function whenIMark_AsDone($task) {
        $this->resource->doDone($task);
    }

    private function whenIMark_AsOpen($task) {
        $this->resource->doOpen($task);
    }

    private function whenIGetTheSchedule() {
        $this->presenter = $this->resource->doGet();
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
        $this->assertEquals($string, $this->getSlot($int)['task']['deadline']['absolute']);
    }

    private function thenTheBufferOfSlot_ShouldBe($int, $string) {
        $this->assertEquals($string, $this->getSlot($int)['task']['deadline']['buffer']);
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

    private function thenSlot_ShouldBeLate($int) {
        $this->assertTrue($this->getSlot($int)['isLate']);
    }

    private function thenSlot_ShouldNotBeLate($int) {
        $this->assertFalse($this->getSlot($int)['isLate']);
    }

}