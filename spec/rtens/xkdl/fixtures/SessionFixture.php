<?php
namespace spec\rtens\xkdl\fixtures;

use rtens\mockster\Mock;
use rtens\mockster\MockFactory;
use rtens\mockster\Mockster;
use rtens\xkdl\web\Session;
use watoki\scrut\Fixture;

/**
 * @property ConfigFixture config <-
 */
class SessionFixture extends Fixture {

    /** @var Session|Mock */
    private $session;

    public function setUp() {
        parent::setUp();

        $mf = new MockFactory();
        $this->session = $mf->getInstance(Session::$CLASS);
        $this->spec->factory->setSingleton(Session::$CLASS, $this->session);
        $this->session->__mock()->mockMethods(Mockster::F_NONE);

        /** @var Session $session */
        $session = $this->session;
        $session->config = $this->config->getConfig();
        $session->setLoggedIn('some@foo.com');
    }

    public function givenIAmNotLoggedIn() {
        $this->session->setLoggedIn(false);
    }

    public function givenIAmLoggedIn() {
        $this->session->setLoggedIn();
    }

    public function givenTheSessionContains_WithTheValue($key, $value) {
        $this->session->set($key, $value);
    }

    public function thenTheSessionShouldContain_WithTheValue($key, $value) {
        $this->spec->assertEquals($value, $this->session->get($key));
    }

    public function thenIShouldBeLoggedInAs($email) {
        $this->spec->assertTrue($this->session->isLoggedIn(), 'Not logged in');
    }

    public function thenIShouldNotBeLoggedIn() {
        $this->spec->assertFalse($this->session->isLoggedIn(), 'Logged in');
    }

} 