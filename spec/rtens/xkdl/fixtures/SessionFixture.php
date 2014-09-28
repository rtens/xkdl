<?php
namespace spec\rtens\xkdl\fixtures;

use rtens\xkdl\exception\AuthenticationException;
use rtens\xkdl\lib\auth\AuthenticatedSession;
use watoki\factory\providers\CallbackProvider;
use watoki\scrut\Fixture;

/**
 * @property ConfigFixture config <-
 */
class SessionFixture extends Fixture {

    public function setUp() {
        parent::setUp();
        $this->givenIAmLoggedInAs('foo@bar.baz');
    }

    public function givenIAmNotLoggedIn() {
        $this->spec->factory->setProvider(AuthenticatedSession::$CLASS, new CallbackProvider(function () {
            throw new AuthenticationException('Not logged in');
        }));
    }

    public function givenIAmLoggedInAs($email) {
        $this->spec->factory->setProvider(AuthenticatedSession::$CLASS, new CallbackProvider(function () use ($email) {
            return new AuthenticatedSession($email, '5 minutes');
        }));
    }

} 