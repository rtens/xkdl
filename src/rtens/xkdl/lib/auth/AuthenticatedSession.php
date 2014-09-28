<?php
namespace rtens\xkdl\lib\auth;

use rtens\xkdl\lib\RandomStringGenerator;
use rtens\xkdl\lib\Time;

class AuthenticatedSession {

    public static $CLASS = __CLASS__;

    /** @var string */
    private $userId;

    /** @var \DateTime */
    private $expirationDate;

    /** @var string */
    private $expirationOffset;

    /** @var string */
    private $token;

    /** @var string */
    private $challenge;

    /** @var null|array */
    public $payload;

    /**
     * @param string $userId
     * @param string $validFor e.g. '5 minutes'
     */
    function __construct($userId, $validFor) {
        $this->userId = $userId;
        $this->expirationOffset = $validFor;
    }

    public function seed($seedToken, RandomStringGenerator $random, Hasher $hash) {
        $challenge = $this->renew($random);
        $this->token = $hash->hash($seedToken, $challenge);
        return $challenge;
    }

    public function renew(RandomStringGenerator $random) {
        $this->challenge = $random->generate();
        $this->expirationDate = null;
        return $this->challenge;
    }

    public function validates($response, Hasher $hash, Time $time) {
        if ($this->isExpired($time) || !$this->isResponseMatchingChallenge($response, $hash)) {
            return false;
        }
        if (!$this->expirationDate) {
            $this->expirationDate = $time->then($this->expirationOffset);
        }
        return true;
    }

    private function isExpired(Time $time) {
        return $this->expirationDate && $this->expirationDate < $time->now();
    }

    private function isResponseMatchingChallenge($response, Hasher $hash) {
        return $response == $hash->hash($this->token, $this->challenge);
    }

    /**
     * @return string
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @return array|null
     */
    public function getPayload() {
        return $this->payload;
    }

    /**
     * @param array|null $payload
     */
    public function setPayload($payload) {
        $this->payload = $payload;
    }

}