<?php
namespace rtens\xkdl\web\root;

use rtens\xkdl\lib\AuthenticationService;
use rtens\xkdl\lib\Configuration;
use rtens\xkdl\lib\EmailService;
use rtens\xkdl\web\Presenter;
use rtens\xkdl\web\RootResource;
use rtens\xkdl\web\Session;
use watoki\curir\http\error\HttpError;
use watoki\curir\http\Response;
use watoki\curir\resource\DynamicResource;
use watoki\curir\Responder;
use watoki\curir\responder\Redirecter;

class AuthResource extends DynamicResource {

    /** @var Session <- */
    public $session;

    /** @var AuthenticationService <- */
    public $authentication;

    /** @var EmailService <- */
    public $email;

    /** @var Configuration <- */
    public $config;

    public function doGet() {
        if ($this->session->isLoggedIn()) {
            return new Redirecter($this->getAncestor(RootResource::$CLASS)->getUrl());
        }

        return new Presenter($this, [
            'sent' => false,
            'login' => false,
        ]);
    }

    public function doPost($email, $tokenOnly = false, $remember = false) {
        $token = $this->authentication->createToken($email);

        $expire = $this->config->then('5 minutes');
        list($challenge, $token) = $this->authentication->createChallenge($email, $token, $expire);
        $this->session->set('response', md5($token . $challenge));

        $this->sendEmail($email, $token, $challenge, $tokenOnly, $remember);

        return new Presenter($this, [
            'challenge' => ['value' => $challenge],
            'sent' => ['to' => $email],
            'email' => false,
            'login' => false,
        ]);
    }

    /**
     * @param string $response
     * @param bool $remember
     * @return Presenter
     * @throws \watoki\curir\http\error\HttpError
     */
    public function doLogin($response, $remember = false) {
        try {
            list($userId, $token) = $this->authentication->validateResponse($response);
            $this->session->setLoggedIn($userId);

            if ($remember) {
                list($challenge, $newToken) = $this->authentication->createChallenge($userId, $token);
                $this->session->set('response', md5($newToken . $challenge));
            }
            return new Presenter($this, [
                'challenge' => isset($challenge) ? ['value' => $challenge] : null,
                'sent' => false,
                'email' => false,
                'login' => ['target' => $this->getAncestor(RootResource::$CLASS)->getUrl()->toString()],
            ]);
        } catch (\Exception $e) {
            throw new HttpError(Response::STATUS_UNAUTHORIZED, $e->getMessage());
        }
    }

    private function sendEmail($email, $token, $challenge, $tokenOnly, $remember) {
        $content = '';

        if (!$tokenOnly) {
            $url = $this->getUrl();
            $url->getParameters()->set('method', 'login');
            $url->getParameters()->set('response', md5($token . $challenge));
            $url->getParameters()->set('remember', print_r($remember, true));

            $url->setFragment($token);
            $content .= $url->toString() . "\n\n";
        }

        $content .= "Token: " . $token;

        $this->email->send($email, 'xkdl@rtens.org', 'xkdl login', $content);
    }

    public function doLogout() {
        $this->session->requireLoggedIn($this);
        $response = $this->session->get('response');
        $this->authentication->logout($response);
        $this->session->setLoggedIn(false);

        return new Redirecter($this->getParent()->getUrl());
    }

} 