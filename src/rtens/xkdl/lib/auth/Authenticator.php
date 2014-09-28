<?php
namespace rtens\xkdl\lib\auth;

use rtens\xkdl\lib\RandomStringGenerator;
use rtens\xkdl\lib\Time;
use watoki\factory\Factory;
use watoki\factory\providers\CallbackProvider;

class Authenticator {

    /** @var Hasher <- */
    public $hasher;

    /** @var RandomStringGenerator <- */
    public $random;

    /** @var Time <- */
    public $time;

    /** @var SessionStore <- */
    public $store;

    /** @var Factory */
    private $factory;

    /**
     * @param Factory $factory <-
     */
    function __construct(Factory $factory) {
        $factory->setSingleton(get_class($this), $this);
        $this->factory = $factory;

        $this->factory->setProvider(AuthenticatedSession::$CLASS, new CallbackProvider(function () {
            throw new InvalidSessionException();
        }));
    }

    public function create($userId, $validFor = '5 minutes') {
        $session = new AuthenticatedSession($userId, $validFor);
        $id = $this->random->generate();
        $this->store->create($session, $id);
        return $session;
    }

    /**
     * @param AuthenticatedSession $session
     * @return array|string[] The seed token and the challenge
     */
    public function initialize(AuthenticatedSession $session) {
        $seed = $this->random->generate();
        $challenge = $session->seed($seed, $this->random, $this->hasher);
        $this->store->update($session);
        return [$seed, $challenge];
    }

    /**
     * @param string $sessionId
     * @param string $response
     * @return AuthenticatedSession
     * @throws InvalidSessionException If response is not valid for this session
     */
    public function authenticate($sessionId, $response) {
        try {
            $session = $this->store->read($sessionId);
            if ($session->validates($response, $this->hasher, $this->time)) {
                $this->store->update($session);
                return $session;
            }
        } catch (\Exception $e) {
        }
        throw new InvalidSessionException();
    }

    /**
     * @param string $sessionId
     * @return string The new challenge
     */
    public function renew($sessionId) {
        $session = $this->store->read($sessionId);
        $challenge = $session->renew($this->random);
        $this->store->update($session);
        return $challenge;
    }

    public function destroy(AuthenticatedSession $session) {
        $this->store->delete($session);
    }

    public function getId(AuthenticatedSession $session) {
        return $this->store->getKey($session);
    }

} 