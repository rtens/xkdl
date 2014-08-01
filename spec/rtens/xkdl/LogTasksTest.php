<?php
namespace spec\rtens\xkdl;

use spec\rtens\xkdl\fixtures\TimeFixture;
use spec\rtens\xkdl\fixtures\WebInterfaceFixture;
use watoki\curir\http\Url;
use watoki\scrut\Specification;
use rtens\xkdl\web\Presenter;
use rtens\xkdl\web\root\ScheduleResource;
use spec\rtens\xkdl\fixtures\ConfigFixture;
use spec\rtens\xkdl\fixtures\FileFixture;
use watoki\curir\responder\Redirecter;

/**
 * @property string fieldTask
 * @property ScheduleResource resource
 * @property \DateTime|null fieldStart
 * @property \DateTime|null fieldEnd
 * @property Presenter presenter
 * @property Redirecter redirecter
 *
 * @property ConfigFixture config <-
 * @property FileFixture file <-
 * @property TimeFixture time <-
 * @property WebInterfaceFixture w <-
 */
class LogTasksTest extends Specification {

    protected function background() {
        $this->time->givenTheTimeZoneIs('GMT0');
    }

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
            "2011-11-11T11:11:00+00:00 >> 2011-11-11T12:12:00+00:00\n");
    }

    function testFinishLoggingForNewTask() {
        $this->file->givenTheFolder('root');
        $this->givenALogHasBeenStartedFor_At('/this/new/task', '2011-11-11 11:11');
        $this->givenIHaveEnteredTheEndTime('2011-11-11 11:12');
        $this->whenIFinishLogging();
        $this->thenNoLoggingShouldBeGoingOn();
        $this->file->thenThereShouldBeAFile_WithTheContent('root/this/new/task/logs.txt',
            "2011-11-11T11:11:00+00:00 >> 2011-11-11T11:12:00+00:00\n");
    }

    function testShowTaskWhenFinishOngoingLogging() {
        $this->file->givenTheFolder('root');
        $this->givenALogHasBeenStartedFor_At('/some/task', '2011-11-11 11:11');
        $this->givenIHaveEnteredTheEndTime('2011-11-11 11:12');
        $this->whenIFinishLogging();
        $this->thenTheTask_ShouldBePresetForLogging('/some/task');
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
            "now >> then\n2011-11-11T11:11:00+00:00 >> 2011-11-11T11:12:00+00:00\n");
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
            "now >> then\n2011-11-11T11:11:00+00:00 >> 2011-11-11T11:12:00+00:00\n");
    }

    function testAddLogToTasksWithMetaInformation() {
        $this->file->givenTheFolder('root/__meta/x_info/__1_task');
        $this->givenIHaveEnteredTheTask('/meta/info/task');
        $this->givenIHaveEnteredTheStartTime('2011-11-11 11:11');
        $this->givenIHaveEnteredTheEndTime('2011-11-11 11:12');
        $this->whenIStartLogging();
        $this->file->thenThereShouldBeAFile_WithTheContent('root/__meta/x_info/__1_task/logs.txt',
            "2011-11-11T11:11:00+00:00 >> 2011-11-11T11:12:00+00:00\n");
    }

    function testShowIdleLogger() {
        $this->file->givenTheFolder('root/first/task');
        $this->file->givenTheFolder('root/second/task');
        $this->file->givenTheFolder('root/done/x_task');
        $this->file->givenTheFolder('root/x_parent/done');
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

    ######################## SET-UP #############################

    protected function setUp() {
        parent::setUp();
        $this->resource = $this->factory->getInstance(ScheduleResource::$CLASS, [Url::parse('schedule')]);

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

    private function whenIGetTheSchedule() {
        $this->presenter = $this->resource->doGet();
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
        $this->redirecter = $this->resource->doStop(new \DateTime($this->fieldEnd));
    }

    private function thenNoLoggingShouldBeGoingOn() {
        $this->assertNull($this->resource->writer->getOngoingLogInfo());
    }

    private function whenICancelLogging() {
        $this->resource->doCancel();
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

    private function thenTheTask_ShouldBePresetForLogging($string) {
        $this->assertEquals($string, $this->redirecter->getTarget()->getParameters()->get('task'));
    }

} 