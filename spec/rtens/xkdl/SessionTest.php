<?php
namespace spec\rtens\xkdl;

use spec\rtens\xkdl\fixtures\FileFixture;
use spec\rtens\xkdl\fixtures\WebInterfaceFixture;
use watoki\scrut\Specification;

/**
 * @property FileFixture file <-
 * @property WebInterfaceFixture web <-
 */
class SessionTest extends Specification {

    protected function background() {
        parent::background();
        $this->web->givenIAmNotLoggedIn();
    }

    function testValidSession() {
        $this->file->givenTheFile_WithContent('session', 'my_session_token');
        $this->web->givenTheSessionContains_WithTheValue('session_token', 'my_session_token');
        $this->web->thenIShouldBeLoggedIn();
    }

    function testWrongToken() {
        $this->file->givenTheFile_WithContent('session', 'my_session_token');
        $this->web->givenTheSessionContains_WithTheValue('session_token', 'wrong_token');
        $this->web->thenIShouldNotBeLoggedIn();
    }

    function testNoSession() {
        $this->web->givenTheSessionContains_WithTheValue('session_token', 'my_session_token');
        $this->web->thenIShouldNotBeLoggedIn();
    }

    function testWriteSession() {
        $this->web->givenIAmLoggedIn();
        $this->file->thenThereShouldBeAFile('session');
        $this->web->thenIShouldBeLoggedIn();
    }

} 