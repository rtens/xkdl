<?php
namespace spec\rtens\xkdl;

use rtens\mockster\Mock;
use rtens\mockster\MockFactory;
use rtens\xkdl\exception\NotLoggedInException;
use rtens\xkdl\lib\EmailService;
use rtens\xkdl\lib\Logger;
use rtens\xkdl\lib\RandomStringGenerator;
use rtens\xkdl\web\RootResource;
use spec\rtens\xkdl\fixtures\ConfigFixture;
use spec\rtens\xkdl\fixtures\FileFixture;
use spec\rtens\xkdl\fixtures\SessionFixture;
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
 * @property FileFixture file <-
 * @property SessionFixture session <-
 */
class AuthenticationTest extends Specification {

    function testRedirectToLoginResource() {
        $this->givenTheRootResourceThrowsA(NotLoggedInException::$CLASS);
        $this->web->whenIGetTheResource('');
        $this->web->thenIShouldBeRedirectedTo('http://xkdl/user');
    }

    function testSendOtpByMail() {
        $this->givenTheNextRandomlyGeneratedOtpIs('password');
        $this->givenIHaveEnteredTheEmail('foo@bar.baz');

        $this->whenIRequestALoginToken();

        $this->thenAnEmailShouldBeSentTo_Containing('foo@bar.baz', 'http://xkdl/user?method=login&otp=password');
        $this->thenTheShouldBeATokenWithTheOtp_For('password', 'foo@bar.baz');

        $this->then_ShouldBeLogged('sent foo@bar.baz');
    }

    function testSuccessfulLogin() {
        $this->givenATokenWithTheOtp_For_WasCreated('foobar', 'foo@bar.baz');
        $this->whenILoginWithTheOtp('foobar');

        $this->session->thenIShouldBeLoggedInAs('foo@bar.baz');
        $this->thenThereShouldBeNoTokens();

        $this->then_ShouldBeLogged('login foo@bar.baz');
    }

    function testWrongOtp() {
        $this->markTestIncomplete();

        $this->givenATokenWithTheOtp_For_WasCreated('foobar', 'foo@bar.baz');
        $this->whenILoginWithTheOtp('wrong');

        $this->thenTheError_ShouldBeRaised('Invalid login.');
        $this->thenIShouldNotBeLoggedIn();
        $this->thenTheShouldBeATokenWithTheOtp_For('foobar', 'foo@bar.baz');

        $this->then_ShouldBeLogged('invalid');
    }

    function testOtpTimeOut() {
        $this->markTestIncomplete();

        $this->givenATokenWithTheOtp_For_WasCreated('foobar', 'foo@bar.baz', '5 minutes 1 second ago');
        $this->whenILoginWithTheOtp('foobar');

        $this->thenTheError_ShouldBeRaised('Login timed out. Please try again.');
        $this->thenIShouldNotBeLoggedIn();
        $this->thenThereShouldBeNoTokens();

        $this->then_ShouldBeLogged('timeout');
    }

    function testLogout() {
        $this->markTestIncomplete();

        $this->givenIAmLoggedInAs('foo@bar.baz');
        $this->whenILogOut();
        $this->thenIShouldNotBeLoggedIn();

        $this->then_ShouldBeLogged('logout foo@bar.baz');
    }

    ########################## SET-UP ###########################

    /** @var Mock */
    private $email;

    /** @var Mock */
    private $generator;

    /** @var Mock */
    private $logger;

    protected function setUp() {
        parent::setUp();

        $mf = new MockFactory();
        $this->email = $this->factory->setSingleton(EmailService::$CLASS,
            $mf->getMock(EmailService::$CLASS));
        $this->generator = $this->factory->setSingleton(RandomStringGenerator::$CLASS,
            $mf->getMock(RandomStringGenerator::$CLASS));
        $this->logger = $this->factory->setSingleton(Logger::$CLASS,
            $mf->getMock(Logger::$CLASS));
    }

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

        $this->factory->setSingleton(RootResource::$CLASS,
            $this->factory->getInstance($className, [Url::parse('http://xkdl')]));
    }

    private function givenTheNextRandomlyGeneratedOtpIs($string) {
        $this->generator->__mock()->method('generate')->willReturn($string);
    }

    private function givenIHaveEnteredTheEmail($string) {
        $this->web->givenTheParameter_Is('email', $string);
    }

    private function whenIRequestALoginToken() {
        $this->web->whenICallTheResource_WithTheMethod('user', 'post');
    }

    private function thenAnEmailShouldBeSentTo_Containing($receiver, $string) {
        $history = $this->email->__mock()->method('send')->getHistory();
        $this->assertTrue($history->wasCalledWith(['to' => $receiver]));
        $this->assertContains($string, $history->getCalledArgumentAt(0, 'body'));
    }

    private function thenTheShouldBeATokenWithTheOtp_For($otp, $email) {
        $this->file->thenThereShouldBeAFile_WithTheContent('otp/' . $otp, $email);
    }

    private function then_ShouldBeLogged($string) {
        $this->assertTrue($this->logger->__mock()->method('log')->getHistory()->wasCalledWith(['message' => $string]));
    }

    private function givenATokenWithTheOtp_For_WasCreated($otp, $email) {
        $this->file->givenTheFile_WithContent('otp/' . $otp, $email);
    }

    private function whenILoginWithTheOtp($otp) {
        $this->web->givenTheParameter_Is('otp', $otp);
        $this->web->whenICallTheResource_WithTheMethod('user', 'login');
    }

    private function thenThereShouldBeNoTokens() {
        $this->file->then_ShouldBeEmpty('otp');
    }

} 