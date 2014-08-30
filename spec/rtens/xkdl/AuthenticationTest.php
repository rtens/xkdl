<?php
namespace spec\rtens\xkdl;

use rtens\xkdl\exception\NotLoggedInException;
use rtens\xkdl\web\RootResource;
use spec\rtens\xkdl\fixtures\ConfigFixture;
use spec\rtens\xkdl\fixtures\WebInterfaceFixture;
use watoki\curir\http\Url;
use watoki\scrut\Specification;

/**
 * Users can authenticate using their email address and a one-time-password sent
 * via email. Sessions are valid for a long time to avoid frequent re-login.
 *
 * The email address is used to identify a user. Thus no registration is necessary.
 *
 * @property ConfigFixture config <-
 * @property WebInterfaceFixture web <-
 */
class AuthenticationTest extends Specification {

    protected function background() {
        $this->config->givenNowIs('2001-01-01 12:00');
//        $this->givenMyIpAddressIs('128.12.42.8');
    }

    function testRedirectToLoginResource() {
        $this->givenTheRootResourceThrowsA(NotLoggedInException::$CLASS);
        $this->web->whenIGetTheResource('');
        $this->web->thenIShouldBeRedirectedTo('http://xkdl/login');
    }

    function testSendOtpByMail() {
        $this->markTestIncomplete();

        $this->givenTheNextRandomlyGeneratedOtpIs('password');
        $this->givenIHaveEnteredTheEmail('foo@bar.baz');
        $this->whenIAskToLogin();
        $this->thenAnEmailShouldBeSentTo_ContainingALoginLinkWithTheOtp('foo@bar.baz', 'password');
        $this->thenTheShouldBeATokenWithTheOtp_For('password', 'foo@bar.baz');

        $this->thenTheLine_ShouldBeLogged('2001-01-01 12:00:00; 128.12.42.8; sent; foo@bar.baz');
    }

    function testSuccessfulLogin() {
        $this->markTestIncomplete();

        $this->givenATokenWithTheOtp_For_WasCreated('foobar', 'foo@bar.baz', '5 minutes ago');
        $this->whenILoginWithTheOtp('foobar');
        $this->thenIShouldBeLoggedInAs('foo@bar.baz');
        $this->thenThereShouldBeNoTokens();

        $this->thenTheLine_ShouldBeLogged('2001-01-01 12:00:00; 128.12.42.8; login; foo@bar.baz');
    }

    function testWrongOtp() {
        $this->markTestIncomplete();

        $this->givenATokenWithTheOtp_For_WasCreated('foobar', 'foo@bar.baz', 'now');
        $this->whenILoginWithTheOtp('wrong');

        $this->thenTheError_ShouldBeRaised('Invalid login.');
        $this->thenIShouldNotBeLoggedIn();
        $this->thenTheShouldBeATokenWithTheOtp_For('foobar', 'foo@bar.baz');

        $this->thenTheLine_ShouldBeLogged('2001-01-01 12:00:00; 128.12.42.8; invalid');
    }

    function testOtpTimeOut() {
        $this->markTestIncomplete();

        $this->givenATokenWithTheOtp_For_WasCreated('foobar', 'foo@bar.baz', '5 minutes 1 second ago');
        $this->whenILoginWithTheOtp('foobar');

        $this->thenTheError_ShouldBeRaised('Login timed out. Please try again.');
        $this->thenIShouldNotBeLoggedIn();
        $this->thenThereShouldBeNoTokens();

        $this->thenTheLine_ShouldBeLogged('2001-01-01 12:00:00; 128.12.42.8; timeout');
    }

    function testLogout() {
        $this->markTestIncomplete();

        $this->givenIAmLoggedInAs('foo@bar.baz');
        $this->whenILogOut();
        $this->thenIShouldNotBeLoggedIn();

        $this->thenTheLine_ShouldBeLogged('2001-01-01 12:00:00; 128.12.42.8; logout; foo@bar.baz');
    }

    ########################## SET-UP ###########################

    private function givenTheRootResourceThrowsA($exceptionClass) {
        $className = "ExceptionThrowingRootResource";
        if (!class_exists($className)) {
            $code = "class $className extends \\rtens\\xkdl\\web\\RootResource {
                public function doGet() {
                    throw new $exceptionClass(\\watoki\\curir\\http\\Url::parse('http://xkdl/bar'));
                }
            }";
            eval($code);
        }

        $root = $this->factory->getInstance($className, [Url::parse('http://xkdl')]);
        $this->factory->setSingleton(RootResource::$CLASS, $root);
    }

} 