<?php
namespace spec\rtens\xkdl;

use rtens\mockster\Mock;
use rtens\mockster\MockFactory;
use spec\rtens\xkdl\fixtures\ConfigFixture;
use spec\rtens\xkdl\fixtures\WebInterfaceFixture;
use watoki\curir\http\Response;
use watoki\curir\http\Url;
use watoki\scrut\Specification;

/**
 * @property ConfigFixture config <-
 * @property WebInterfaceFixture web <-
 */
class AuthenticationTest extends Specification {

    protected function background() {
        parent::background();

        $this->config->givenTheUsersHasTheOpenId('http://some.open.id/foo');
        $this->givenTheAuthUrlIs('http://some.auth.url/bar');
    }

    function testRequireToLogin() {
        $this->web->givenIAmNotLoggedIn();
        $this->web->whenIGetTheResource('schedule');
        $this->web->thenIShouldBeRedirectedTo('http://some.auth.url/bar');
    }

    function testLoggedInUser() {
        $this->web->givenIAmLoggedIn();
        $this->web->whenIGetTheResource('schedule');
        $this->web->thenIShouldNotBeRedirected();
    }

    function testLogIn() {
        $this->web->givenIAmNotLoggedIn();
        $this->whenILogInWithTheIdentity('http://some.open.id/foo');
        $this->web->thenIShouldBeLoggedIn();
    }

    function testFailedLogIn() {
        $this->web->givenIAmNotLoggedIn();
        $this->whenICancelTheLogIn();
        $this->web->thenAnErrorWithTheStatus_ShouldOccur(Response::STATUS_UNAUTHORIZED);
        $this->web->thenIShouldNotBeLoggedIn();
    }

    function testLogInWithWrongId() {
        $this->web->givenIAmNotLoggedIn();
        $this->whenITryToLogInWithTheIdentity('http://wrong.open.id/foo');
        $this->web->thenAnErrorWithTheStatus_ShouldOccur(Response::STATUS_UNAUTHORIZED);
        $this->web->thenIShouldNotBeLoggedIn();
    }

    /***************************** STEPS *******************************/

    /** @var Mock */
    private $openId;

    protected function setUp() {
        parent::setUp();

        $mf = new MockFactory();
        $this->openId = $mf->getInstance('LightOpenID');
        $this->factory->setSingleton('LightOpenID', $this->openId);
    }

    private function givenTheAuthUrlIs($url) {
        $this->openId->__mock()->method('authUrl')->willReturn(Url::parse($url));
    }

    private function whenILogInWithTheIdentity($string) {
        $this->openId->__mock()->method('validate')->willReturn(true);
        $this->openId->__mock()->method('__get')->willReturn($string)->withArguments('identity');
        $this->web->whenICallTheResource_WithTheMethod('user', 'login');
    }

    private function whenICancelTheLogIn() {
        $this->openId->__mock()->method('validate')->willReturn(false);
        $this->web->whenITryToCallTheResource_WithTheMethod('user', 'login');
    }

    private function whenITryToLogInWithTheIdentity($string) {
        $this->openId->__mock()->method('validate')->willReturn(true);
        $this->openId->__mock()->method('__get')->willReturn($string)->withArguments('identity');
        $this->web->whenITryToCallTheResource_WithTheMethod('user', 'login');
    }

} 