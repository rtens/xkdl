<?php
namespace rtens\xkdl\lib;

use watoki\curir\http\Url;

class AuthenticationService {

    /** @var RandomStringGenerator <- */
    public $generator;

    /** @var EmailService <- */
    public $email;

    /** @var Configuration <- */
    public $config;

    /** @var Logger <- */
    public $logger;

    public function request($email, Url $login, $key) {
        $otp = $this->generator->generate();

        $this->createToken($email, $otp);
        $this->sendEmail($email, $login, $key, $otp);

        $this->logger->log($this, 'sent foo@bar.baz');
    }

    private function createToken($email, $otp) {
        $otpDir = $this->config->userFolder() . '/otp';
        if (!file_exists($otpDir)) {
            mkdir($otpDir);
        }
        file_put_contents($otpDir . '/' . $otp, $email);
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
}