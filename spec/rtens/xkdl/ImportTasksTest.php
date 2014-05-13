<?php
namespace spec\rtens\xkdl;

use rtens\xkdl\task\GoogleCalendarTask;
use spec\rtens\xkdl\fixtures\GoogleApiFixture;
use spec\rtens\xkdl\fixtures\TaskStorageFixture;
use spec\rtens\xkdl\fixtures\WebInterfaceFixture;
use watoki\curir\http\Response;
use watoki\scrut\Specification;

/**
 * @property TaskStorageFixture task <-
 * @property GoogleApiFixture api <-
 * @property WebInterfaceFixture web <-
 */
class ImportGoogleCalendarTest extends Specification {

    function testAuthenticationWithGoogleApi() {
        $this->markTestIncomplete();

        $this->task->givenTheTask_OfType('gc', GoogleCalendarTask::$CLASS);
        $this->api->givenTheAuthenticationUrlIs('http://auth.example.com');
        $this->api->givenIAmNotAuthenticated();
        $this->web->givenTheParameter_Is('from', 'now');
        $this->web->givenTheParameter_Is('until', 'tomorrow');
        $this->web->whenICallTheResource_WithTheMethod('schedule', 'post');
        $this->web->thenTheResponseStatusShouldBe(Response::STATUS_UNAUTHORIZED);
        $this->web->thenIShouldBeRedirectedTo('http://auth.example.com');

        $this->api->givenTheAccessTokenIs('some-token');
        $this->web->givenTheParameter_Is('code', 'some-code');
        $this->web->whenICallTheResource_WithTheMethod('', 'authenticate');
        $this->api->thenIShouldBeAuthenticatedWith('some-code');
        $this->web->thenTheSessionShouldContain_WithTheValue('token', 'some-token');
    }

    function testSaveTokenInSession() {
        $this->web->givenTheSessionContains_WithTheValue('token', 'my-token');
        $this->web->whenICallTheResource_WithTheMethod('', 'get');
        $this->api->thenTheAccesToken_ShouldBeSet('my-token');
    }

    function testReadGoogleCalendarTask() {
        $this->markTestIncomplete();

        $this->givenTheFile_Containing('root/some/task/__.txt',
            "type: rtens\\xkdl\\task\\GoogleCalendar\n" .
            "clientId: mycliend-id\n" .
            "clientSecret: mysecret\n" .
            "developerKey: my-developer-key");

        $this->whenIReadTheTask('some/task');
    }

    function testNoCalendars() {
        $this->markTestIncomplete();
    }

    function testCreateTasksForEvents() {
        $this->markTestIncomplete();

        $this->givenTheCalendar('My Calendar');
        $this->givenTheEvent_In_Starting_AndEnding('This event', 'My Calendar', '2011-11-11 11:00', '2011-11-11 12:00');
        $this->givenTheEvent_In_Starting_AndEnding('That event', 'My Calendar', '2011-11-12 12:00', '2011-11-11 13:00');

        $this->givenTheCalendar('Other One');
        $this->givenTheEvent_In_Starting_AndEnding('This event', 'Other One', '2011-11-11 12:00', '2011-11-11 13:00');
        $this->givenTheEvent_In_Starting_AndEnding('That event', 'Other One', '2011-11-12 14:00', '2011-11-11 15:00');

        $this->givenTheFolder('root/some/task/My Calendar/That event');
        $this->givenTheFile_Containing('root/some/task/My Calendar/That event/logs.txt', '8:00 >> 9:00');

        // with calendars as children, events are the calendars' children
        // Each event becomes a task with duration window and deadline
    }

    function testLoadLogsOfEventTask() {
        $this->markTestIncomplete();

        $this->givenTheCalendar('My Calendar');
        $this->givenTheEvent_In_Starting_AndEnding('This event', 'My Calendar', '2011-11-11 11:00', '2011-11-11 12:00');

        $this->givenTheFolder('root/some/task/My Calendar/That event');
        $this->givenTheFile_Containing('root/some/task/My Calendar/That event/logs.txt', '8:00 >> 9:00');

    }

}