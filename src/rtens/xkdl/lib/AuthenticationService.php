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
     * @param $token
     * @return string ID of user authenticated by token
     */
    public function validateToken($token) {
        $file = $this->tokenFile($token);

        if (!file_exists($file)) {
            $this->error('Invalid login');
        }

        $token = json_decode(file_get_contents($file), true);
        unlink($file);

        $userId = $token['userId'];
        $expire = new \DateTime($token['expire']);

        if ($expire < $this->config->now()) {
            $this->error('Login timed out', ' for ' . $userId);
        }

        $this->logger->log($this, 'login ' . $userId);

        return $userId;
    }

    /**
     * @param $userId
     * @param \DateTime $expire
     * @return string The token
     */
    public function createToken($userId, \DateTime $expire = null) {
        $token = $this->generator->generate();
        $expire = $expire ? : new \DateTime(self::DEFAULT_EXPIRATION);
        $this->storeToken($userId, $token, $expire);
        $this->logger->log($this, 'created ' . $userId .
            ' valid ' . $this->config->now()->diff($expire)->format('%ad %hh %im %ss'));
        return $token;
    }

    private function storeToken($userId, $token, \DateTime $expire) {
        $file = $this->tokenFile($token);
        if (!file_exists(dirname($file))) {
            mkdir(dirname($file));
        }

        $token = [
            'userId' => $userId,
            'expire' => $expire->format('c')
        ];
        file_put_contents($file, json_encode($token));
    }

    private function tokenFile($token) {
        return $this->config->userFolder() . '/token/' . $token;
    }

    private function error($string, $log = '') {
        $this->logger->log($this, $string . $log);
        throw new \Exception($string);
    }

    public function logout($userId) {
        $this->logger->log($this, 'logout ' . $userId);
    }
}