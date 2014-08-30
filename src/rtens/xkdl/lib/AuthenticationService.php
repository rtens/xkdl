<?php
namespace rtens\xkdl\lib;

use watoki\curir\http\error\HttpError;
use watoki\curir\http\Response;
use watoki\curir\http\Url;

class AuthenticationService {

    const TIMEOUT = 'PT5M';

    /** @var RandomStringGenerator <- */
    public $generator;

    /** @var EmailService <- */
    public $email;

    /** @var Configuration <- */
    public $config;

    /** @var Logger <- */
    public $logger;

    public function authenticate($otp) {
        $file = $this->tokenFile($otp);

        if (!file_exists($file)) {
            $this->error('Invalid login');
        }

        $token = json_decode(file_get_contents($file), true);
        unlink($file);

        $email = $token['email'];
        $created = new \DateTime($token['created']);

        if ($created->add(new \DateInterval(self::TIMEOUT)) < $this->config->now()) {
            $this->error('Login timed out', ' for ' . $email);
        }

        $this->logger->log($this, 'login ' . $email);

        return $email;
    }

    public function request($email, Url $login, $key) {
        $otp = $this->generator->generate();

        $this->createToken($email, $otp);
        $this->sendEmail($email, $login, $key, $otp);

        $this->logger->log($this, 'sent ' . $email);
    }

    private function createToken($email, $otp) {
        $file = $this->tokenFile($otp);
        if (!file_exists(dirname($file))) {
            mkdir(dirname($file));
        }

        $token = [
            'email' => $email,
            'created' => $this->config->now()->format('c')
        ];
        file_put_contents($file, json_encode($token));
    }

    /**
     * @param $email
     * @param Url $login
     * @param $key
     * @param $otp
     */
    private function sendEmail($email, Url $login, $key, $otp) {
        $login->getParameters()->set($key, $otp);
        $this->email->send($email, 'xkdl@rtens.org', 'xkdl login', $login->toString());
    }

    private function tokenFile($otp) {
        return $this->config->userFolder() . '/otp/' . $otp;
    }

    private function error($string, $log = '') {
        $this->logger->log($this, $string . $log);
        throw new HttpError(Response::STATUS_UNAUTHORIZED, $string);
    }

    public function logout($email) {
        $this->logger->log($this, 'logout ' . $email);
    }
}