<?php
namespace spec\rtens\xkdl;

use rtens\mockster\Mock;
use rtens\mockster\MockFactory;
use rtens\xkdl\lib\EmailSender;
use rtens\xkdl\lib\Logger;
use rtens\xkdl\lib\RandomStringGenerator;
use rtens\xkdl\web\RootResource;
use spec\rtens\xkdl\fixtures\ConfigFixture;
use spec\rtens\xkdl\fixtures\FileFixture;
use spec\rtens\xkdl\fixtures\SessionFixture;
use spec\rtens\xkdl\fixtures\WebInterfaceFixture;
use watoki\curir\delivery\WebResponse;
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
        $this->markTestIncomplete();

        $this->session->givenIAmNotLoggedIn();
        $this->file->givenPathsAreRelativeToTheUserFolder();
    }

    function testRedirectToLoginResource() {
        $this->givenTheRootResourceThrowsA(NotLoggedInException::$CLASS);
        $this->web->whenIGetTheResource('');
        $this->web->thenIShouldBeRedirectedTo('http://xkdl/auth');
    }

    function testSendTokenByMail() {
        $this->givenTheNextRandomlyGeneratedStringIs('myChallenge');
        $this->givenTheNextRandomlyGeneratedStringIs('password');

        $this->givenIHaveEnteredTheEmail('foo@bar.baz');

        $this->whenIRequestALoginToken();

        $this->thenAnEmailShouldBeSentTo_Containing('foo@bar.baz', 'http://xkdl/auth?method=login');
        $this->thenAnEmailShouldBeSentTo_ContainingTheToken_After('foo@bar.baz', 'password', 'myChallenge');
        $this->thenThereShouldBeAResponseFor_WithTheToken_For('myChallenge', 'password', 'foo@bar.baz');

        $this->then_ShouldBeLogged('created foo@bar.baz');
        $this->web->thenTheResponseBodyShouldContain('myChallenge');
    }

    function testSuccessfulLogin() {
        $this->givenAChallenge_WithTheToken_WasCreatedFor('theChallenge', 'theToken', 'Foo@Bar.baz');
        $this->givenTheNextRandomlyGeneratedStringIs('nextChallenge');

        $this->whenILoginWithTheResponseOf_AndTheToken('theChallenge', 'theToken');

        $this->session->thenIShouldBeLoggedInAs('foo@bar.baz');
        $this->thenThereShouldBeAResponseFor_WithTheToken_After_For('nextChallenge', 'theToken', 'theChallenge', 'Foo@Bar.baz');

        $this->then_ShouldBeLogged('login Foo@Bar.baz');
        $this->web->thenTheResponseBodyShouldContain('nextChallenge');
    }

    function testWrongToken() {
        $this->givenAChallenge_WithTheToken_WasCreatedFor('foobar', 'password', 'foo@bar.baz');
        $this->whenITryToLoginWithTheResponseOf_AndTheToken('foobar', 'wrong');

        $this->web->thenAnErrorWithTheStatus_ShouldOccur(WebResponse::STATUS_UNAUTHORIZED);
        $this->session->thenIShouldNotBeLoggedIn();
        $this->thenThereShouldBeAResponseFor_WithTheToken_For('foobar', 'password', 'foo@bar.baz');

        $this->then_ShouldBeLogged('Invalid login');
    }

    function testTimeOut() {
        $this->givenAChallenge_WithTheToken_WasCreatedFor('challenge', 'password', 'foo@bar.baz', '5 minutes 1 second ago');
        $this->whenITryToLoginWithTheResponseOf_AndTheToken('challenge', 'password');

        $this->web->thenAnErrorWithTheStatus_ShouldOccur(WebResponse::STATUS_UNAUTHORIZED);
        $this->session->thenIShouldNotBeLoggedIn();
        $this->thenThereShouldBeNoTokens();

        $this->then_ShouldBeLogged('Login timed out for foo@bar.baz');
    }

    function testLogout() {
        $this->givenAChallenge_WithTheToken_WasCreatedFor('theChallenge', 'theToken', 'Foo@Bar.baz');
        $this->whenILoginWithTheResponseOf_AndTheToken('theChallenge', 'theToken');

        $this->whenILogOut();
        $this->session->thenIShouldNotBeLoggedIn();
        $this->thenThereShouldBeNoTokens();

        $this->then_ShouldBeLogged('logout Foo@Bar.baz');
        $this->web->thenIShouldBeRedirectedTo('http://xkdl');
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
        $this->email = $this->factory->setSingleton(EmailSender::$CLASS,
            $mf->getMock(EmailSender::$CLASS));
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
                    throw new $exceptionClass(\\watoki\\curir\\protocol\\Url::fromString('http://xkdl/bar'));
                }
            }";
            eval($code);
        }

        $this->factory->setSingleton(RootResource::$CLASS,
            $this->factory->getInstance($className));
    }

    private function givenTheNextRandomlyGeneratedStringIs($string) {
        $this->generator->__mock()->method('generate')->willReturn($string)->once();
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

    private function thenThereShouldBeAResponseFor_WithTheToken_For($challenge, $token, $email) {
        $token = md5($token . $challenge);
        $file = 'token/' . md5($token . $challenge);
        $this->file->thenThereShouldBeAFile_ThatContains($file, $email);
        $this->file->thenThereShouldBeAFile_ThatContains($file, $token);
    }

    private function thenThereShouldBeAResponseFor_WithTheToken_After_For($challenge, $token, $previousChallenge, $email) {
        $token = md5(md5($token . $previousChallenge) . $challenge);
        $file = 'token/' . md5($token . $challenge);
        $this->file->thenThereShouldBeAFile_ThatContains($file, $email);
        $this->file->thenThereShouldBeAFile_ThatContains($file, $token);
    }

    private function then_ShouldBeLogged($string) {
        $history = $this->logger->__mock()->method('log')->getHistory();
        $this->assertTrue($history->wasCalledWith(['message' => $string]), $history->toString());
    }

    private function givenAChallenge_WithTheToken_WasCreatedFor($challenge, $token, $email, $when = 'now') {
        $this->config->givenNowIs($when);
        $this->givenTheNextRandomlyGeneratedStringIs($challenge);
        $this->givenTheNextRandomlyGeneratedStringIs($token);
        $this->givenIHaveEnteredTheEmail($email);
        $this->whenIRequestALoginToken();
        $this->config->givenNowIs('now');
    }

    private function whenILoginWithTheResponseOf_AndTheToken($challenge, $token) {
        $this->web->givenTheParameter_Is('response', md5(md5($token . $challenge) . $challenge));
        $this->web->givenTheParameter_Is('remember', 'true');
        $this->web->whenICallTheResource_WithTheMethod('auth', 'login');
    }

    private function whenITryToLoginWithTheResponseOf_AndTheToken($challenge, $token) {
        $this->web->givenTheParameter_Is('response', md5(md5($token . $challenge) . $challenge));
        $this->web->whenITryToCallTheResource_WithTheMethod('auth', 'login');
    }

    private function thenThereShouldBeNoTokens() {
        $this->file->then_ShouldBeEmpty('token');
    }

    private function whenILogOut() {
        $this->web->whenICallTheResource_WithTheMethod('auth', 'logout');
    }

    private function thenAnEmailShouldBeSentTo_ContainingTheToken_After($receiver, $token, $challenge) {
        $this->thenAnEmailShouldBeSentTo_Containing($receiver, md5($token . $challenge));
    }

} 