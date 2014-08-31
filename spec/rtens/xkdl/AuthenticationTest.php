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
use watoki\curir\http\Response;
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

    protected function background() {
        $this->session->givenIAmNotLoggedIn();
        $this->file->givenPathsAreRelativeToTheUserFolder();
    }

    function testRedirectToLoginResource() {
        $this->givenTheRootResourceThrowsA(NotLoggedInException::$CLASS);
        $this->web->whenIGetTheResource('');
        $this->web->thenIShouldBeRedirectedTo('http://xkdl/auth');
    }

    function testSendTokenByMail() {
        $this->givenTheNextRandomlyGeneratedTokenIs('password');
        $this->givenIHaveEnteredTheEmail('foo@bar.baz');

        $this->whenIRequestALoginToken();

        $this->thenAnEmailShouldBeSentTo_Containing('foo@bar.baz', 'http://xkdl/auth?method=login&token=password');
        $this->thenTheShouldBeATokenWithTheToken_For('password', 'foo@bar.baz');

        $this->then_ShouldBeLogged('created foo@bar.baz valid 0d 0h 5m 0s');
    }

    function testSuccessfulLogin() {
        $this->givenATokenWithTheToken_For_WasCreated('foobar', 'Foo@Bar.baz');
        $this->givenTheNextRandomlyGeneratedTokenIs('next');

        $this->whenILoginWithTheToken('foobar');

        $this->session->thenIShouldBeLoggedInAs('foo@bar.baz');
        $this->thenTheShouldBeATokenWithTheToken_For('next', 'foo@bar.baz');
        $this->web->thenACookie_WithTheValue_ShouldBeSet('token', 'next');

        $this->then_ShouldBeLogged('login Foo@Bar.baz');
    }

    function testWrongToken() {
        $this->givenATokenWithTheToken_For_WasCreated('foobar', 'foo@bar.baz');
        $this->whenITryToLoginWithTheToken('wrong');

        $this->web->thenAnErrorWithTheStatus_ShouldOccur(Response::STATUS_UNAUTHORIZED);
        $this->session->thenIShouldNotBeLoggedIn();
        $this->thenTheShouldBeATokenWithTheToken_For('foobar', 'foo@bar.baz');

        $this->then_ShouldBeLogged('Invalid login');
    }

    function testTimeOut() {
        $this->givenATokenWithTheToken_For_WasCreated('foobar', 'foo@bar.baz', '5 minutes 1 second ago');
        $this->whenITryToLoginWithTheToken('foobar');

        $this->web->thenAnErrorWithTheStatus_ShouldOccur(Response::STATUS_UNAUTHORIZED);
        $this->session->thenIShouldNotBeLoggedIn();
        $this->thenThereShouldBeNoTokens();

        $this->then_ShouldBeLogged('Login timed out for foo@bar.baz');
    }

    function testLogout() {
        $this->session->givenIAmLoggedInAs('foo@bar.baz');
        $this->whenILogOut();
        $this->session->thenIShouldNotBeLoggedIn();

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

    private function givenTheNextRandomlyGeneratedTokenIs($string) {
        $this->generator->__mock()->method('generate')->willReturn($string);
    }

    private function givenIHaveEnteredTheEmail($string) {
        $this->web->givenTheParameter_Is('email', $string);
    }

    private function whenIRequestALoginToken() {
        $this->web->whenICallTheResource_WithTheMethod('auth', 'post');
    }

    private function thenAnEmailShouldBeSentTo_Containing($receiver, $string) {
        $history = $this->email->__mock()->method('send')->getHistory();
        $this->assertTrue($history->wasCalledWith(['to' => $receiver]));
        $this->assertContains($string, $history->getCalledArgumentAt(0, 'body'));
    }

    private function thenTheShouldBeATokenWithTheToken_For($token, $email) {
        $this->file->thenThereShouldBeAFile_ThatContains('token/' . $token, $email);
    }

    private function then_ShouldBeLogged($string) {
        $history = $this->logger->__mock()->method('log')->getHistory();
        $this->assertTrue($history->wasCalledWith(['message' => $string]), $history->toString());
    }

    private function givenATokenWithTheToken_For_WasCreated($token, $email, $when = 'now') {
        $this->config->givenNowIs($when);
        $this->givenTheNextRandomlyGeneratedTokenIs($token);
        $this->givenIHaveEnteredTheEmail($email);
        $this->whenIRequestALoginToken();
        $this->config->givenNowIs('now');
    }

    private function whenILoginWithTheToken($token) {
        $this->web->givenTheParameter_Is('token', $token);
        $this->web->whenICallTheResource_WithTheMethod('auth', 'login');
    }

    private function whenITryToLoginWithTheToken($token) {
        $this->web->givenTheParameter_Is('token', $token);
        $this->web->whenITryToCallTheResource_WithTheMethod('auth', 'login');
    }

    private function thenThereShouldBeNoTokens() {
        $this->file->then_ShouldBeEmpty('token');
    }

    private function whenILogOut() {
        $this->web->whenICallTheResource_WithTheMethod('auth', 'logout');
    }

} 