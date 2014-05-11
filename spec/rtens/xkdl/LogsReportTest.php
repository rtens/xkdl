<?php
namespace spec\rtens\xkdl;

use rtens\xkdl\web\Presenter;
use rtens\xkdl\web\root\LogsResource;
use spec\rtens\xkdl\fixtures\TaskFixture;
use watoki\curir\http\Url;
use watoki\curir\Responder;
use watoki\scrut\Specification;

/**
 * @property TaskFixture task <-
 */
class LogsReportTest extends Specification {

    protected function background() {
        $this->task->givenTheRootTask('root');
    }

    public function testNoLogs() {
        $this->whenIRequestAReportOfLogs();
        $this->thenThereShouldBe_Logs(0);
    }

    public function testWithLogs() {
        $this->task->givenIHaveLoggedFrom_Until_For('2001-01-01 12:00:01', '2001-01-01 13:00:02', 'root');
        $this->whenIRequestAReportOfLogs();

        $this->thenThereShouldBe_Logs(1);
        $this->thenLog_ShouldHave(1, 'task', '');
        $this->thenLog_ShouldHave(1, 'start', '2001-01-01 12:00');
        $this->thenLog_ShouldHave(1, 'end', '2001-01-01 13:00');
    }

    public function testWithinTimeSpan() {
        $this->task->givenIHaveLoggedFrom_Until_For('2011-11-11 10:00', '2011-11-11 12:00', 'root');
        $this->task->givenIHaveLoggedFrom_Until_For('2011-11-11 12:15', '2011-11-11 12:30', 'root');
        $this->task->givenIHaveLoggedFrom_Until_For('2011-11-11 13:00', '2011-11-11 14:30', 'root');
        $this->task->givenIHaveLoggedFrom_Until_For('2011-11-11 15:00', '2011-11-11 16:00', 'root');

        $this->whenIRequestAReportOfLogsBetween_And('2011-11-11 11:00', '2011-11-11 14:00');

        $this->thenThereShouldBe_Logs(3);
        $this->thenLog_ShouldHave(1, 'start', '2011-11-11 10:00');
        $this->thenLog_ShouldHave(2, 'start', '2011-11-11 12:15');
        $this->thenLog_ShouldHave(3, 'start', '2011-11-11 13:00');
        $this->thenLog_ShouldHave(3, 'end', '2011-11-11 14:30');

        $this->thenLog_ShouldHave(1, 'time', '2:00');
        $this->thenLog_ShouldHave(2, 'time', '0:15');
        $this->thenTheTotalShouldBe('3:45');
    }

    public function testAfterDate() {
        $this->task->givenIHaveLoggedFrom_Until_For('2011-11-11 10:00', '2011-11-11 12:00', 'root');
        $this->task->givenIHaveLoggedFrom_Until_For('2011-11-11 13:00', '2011-11-11 15:00', 'root');

        $this->whenIRequestAReportOfLogsAfter('2011-11-11 14:00');

        $this->thenThereShouldBe_Logs(1);
        $this->thenLog_ShouldHave(1, 'start', '2011-11-11 13:00');
    }

    public function testOnlySubTasks() {
        $this->task->givenTheTask_In('task1', 'root');
        $this->task->givenTheTask_In('task2', 'task1');
        $this->task->givenTheTask_In('task3', 'task2');
        $this->task->givenTheTask_In('task4', 'task1');
        $this->task->givenTheTask_In('task5', 'root');

        $this->task->givenIHaveLoggedFrom_Until_For('12:00', '13:00', 'task1');
        $this->task->givenIHaveLoggedFrom_Until_For('13:00', '14:00', 'task2');
        $this->task->givenIHaveLoggedFrom_Until_For('14:00', '15:00', 'task3');
        $this->task->givenIHaveLoggedFrom_Until_For('15:00', '16:00', 'task4');
        $this->task->givenIHaveLoggedFrom_Until_For('16:00', '17:00', 'task5');

        $this->whenIRequestAReportOfLogsUnder('task1');

        $this->thenThereShouldBe_Logs(4);
        $this->thenLog_ShouldHave(1, 'task', '/task1');
        $this->thenLog_ShouldHave(2, 'task', '/task1/task2');
        $this->thenLog_ShouldHave(3, 'task', '/task1/task2/task3');
        $this->thenLog_ShouldHave(4, 'task', '/task1/task4');
    }

    public function testSortByStart() {
        $this->task->givenTheTask_In('task1', 'root');
        $this->task->givenTheTask_In('task2', 'root');

        $this->task->givenIHaveLoggedFrom_Until_For('2011-11-11 12:00', '2011-11-11 13:00', 'task1');
        $this->task->givenIHaveLoggedFrom_Until_For('2011-11-11 10:00', '2011-11-11 11:00', 'task2');

        $this->whenIRequestAReportOfLogsSortedByTime();

        $this->thenThereShouldBe_Logs(2);
        $this->thenLog_ShouldHave(1, 'task', '/task2');
        $this->thenLog_ShouldHave(2, 'task', '/task1');
    }

    ######################## SET-UP ############################

    /** @var Presenter */
    private $responder;

    private function whenIRequestAReportOfLogs() {
        $this->whenIRequestAReportOfLogsBetween_And(null, null);
    }

    private function whenIRequestAReportOfLogsAfter($start) {
        $this->whenIRequestAReportOfLogsBetween_And($start, null);
    }

    private function whenIRequestAReportOfLogsBetween_And($start, $end) {
        $this->whenIRequestAReportOfLogsUnder_Between_And('/', $start, $end);
    }

    private function whenIRequestAReportOfLogsUnder($task) {
        $this->whenIRequestAReportOfLogsUnder_Between_And($task, null, null);
    }

    private function whenIRequestAReportOfLogsSortedByTime() {
        $this->whenIRequestAReportOfLogsUnder_Between_And('/', null, null, true);
    }

    private function whenIRequestAReportOfLogsUnder_Between_And($task, $start, $end, $sortByTime = false) {
        /** @var LogsResource $resource */
        $resource = $this->factory->getInstance(LogsResource::$CLASS, [Url::parse('report')]);
        $this->responder = $resource->doGet($task, $start, $end, $sortByTime);
    }

    private function thenThereShouldBe_Logs($count) {
        $this->assertCount($count, $this->responder->getModel()['log']);
    }

    private function thenLog_ShouldHave($pos, $key, $value) {
        $this->assertEquals($value, $this->responder->getModel()['log'][$pos - 1][$key]);
    }

    private function thenTheTotalShouldBe($string) {
        $this->assertEquals($string, $this->responder->getModel()['total']);
    }

} 