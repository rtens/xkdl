<?php
namespace spec\rtens\xkdl;

use rtens\mockster\Mock;
use rtens\mockster\MockFactory;
use rtens\xkdl\lib\OpenIdAuthenticator;
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
        $this->markTestIncomplete();

        $this->web->givenIAmLoggedIn();
        $this->web->whenIGetTheResource('schedule');
        $this->web->thenIShouldNotBeRedirected();
    }

    function testLogIn() {
        $this->markTestIncomplete();

        $this->web->givenIAmNotLoggedIn();
        $this->whenILogInWithTheIdentity('http://some.open.id/foo');
        $this->thenIShouldBeLoggedIn();
    }

    function testFailedLogIn() {
        $this->markTestIncomplete();

        $this->web->givenIAmNotLoggedIn();
        $this->whenILogInWithTheIdentity('http://wrong.open.id/foo');
        $this->web->thenTheResponseStatusShouldBe(Response::STATUS_UNAUTHORIZED);
    }

    /***************************** STEPS *******************************/

    /** @var Mock */
    private $authenticator;

    protected function setUp() {
        parent::setUp();

        $mf = new MockFactory();
        $this->authenticator = $mf->getInstance(OpenIdAuthenticator::$CLASS);
        $this->factory->setSingleton(OpenIdAuthenticator::$CLASS, $this->authenticator);
    }

    private function givenTheAuthUrlIs($url) {
        $this->authenticator->__mock()->method('getAuthenticationUrl')->willReturn(Url::parse($url));
    }

    private function whenILogInWithTheIdentity($string) {
    }

    private function thenIShouldBeLoggedIn() {
    }

} 