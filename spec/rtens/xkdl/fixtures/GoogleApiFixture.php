<?php
namespace spec\rtens\xkdl\fixtures;

use rtens\mockster\Mock;
use rtens\mockster\MockFactory;
use watoki\scrut\Fixture;

class GoogleApiFixture extends Fixture {

    /** @var Mock */
    private $client;

    private $token;

    public function setUp() {
        parent::setUp();

        $mf = new MockFactory();
        $this->client = $mf->getInstance('Google_Client');
        $this->client->__mock()->method('getAccessToken')->willCall(function () {
            return $this->token;
        });
        $this->spec->factory->setSingleton('Google_Client', $this->client);
    }

    public function thenTheAccesToken_ShouldBeSet($token) {
        $this->spec->assertTrue($this->client->__mock()->method('setAccessToken')->getHistory()
            ->wasCalledWith([$token]));
    }

    public function givenTheAuthenticationUrlIs($url) {
        $this->client->__mock()->method('createAuthUrl')->willReturn($url);
    }

    public function givenIAmNotAuthenticated() {
        $this->token = null;
    }

    public function givenTheAccessTokenIs($token) {
        $this->token = $token;
    }

    public function thenIShouldBeAuthenticatedWith($code) {
        $this->spec->assertTrue($this->client->__mock()->method('authenticate')->getHistory()
            ->wasCalledWith([$code]));
    }
}