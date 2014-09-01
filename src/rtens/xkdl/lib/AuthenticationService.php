<?php
namespace rtens\xkdl\lib;

class AuthenticationService {

    public static $CLASS = __CLASS__;

    const DEFAULT_EXPIRATION = '7 days';

    /** @var RandomStringGenerator <- */
    public $generator;

    /** @var Configuration <- */
    public $config;

    /** @var Logger <- */
    public $logger;

    /**
     * @param $response
     * @return array with userId and token
     */
    public function validateResponse($response) {
        $file = $this->config->userFolder() . '/token/' . $response;

        if (!file_exists($file)) {
            $this->error('Invalid login');
        }

        $response = json_decode(file_get_contents($file), true);
        unlink($file);

        $userId = $response['userId'];
        $expire = new \DateTime($response['expire']);

        $now = $this->config->now();
        if ($expire < $now) {
            $this->error('Login timed out', ' for ' . $userId);
        }

        $this->logger->log($this, 'login ' . $userId);

        return [$userId, $response['token']];
    }

    /**
     * @param $userId
     * @return string The token
     */
    public function createToken($userId) {
        $token = $this->generator->generate();
        $this->logger->log($this, 'created ' . $userId);
        return $token;
    }

    public function createChallenge($userId, $token, \DateTime $expire = null) {
        $challenge = $this->generator->generate();
        $token = md5($token . $challenge);

        $file = $this->config->userFolder() . '/token/' . md5($token . $challenge);
        if (!file_exists(dirname($file))) {
            mkdir(dirname($file));
        }

        $expire = $expire ? : $this->config->then(self::DEFAULT_EXPIRATION);
        $token = [
            'userId' => $userId,
            'token' => $token,
            'expire' => $expire->format('c')
        ];
        file_put_contents($file, json_encode($token));

        return $challenge;
    }

    private function error($string, $log = '') {
        $this->logger->log($this, $string . $log);
        throw new \Exception($string);
    }

    public function logout($userId) {
        $this->logger->log($this, 'logout ' . $userId);
    }
}