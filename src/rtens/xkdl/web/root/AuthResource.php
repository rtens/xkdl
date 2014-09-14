<?php
namespace rtens\xkdl\web\root;

use rtens\xkdl\lib\AuthenticationService;
use rtens\xkdl\lib\Configuration;
use rtens\xkdl\lib\EmailService;
use rtens\xkdl\web\Session;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\error\HttpError;
use watoki\curir\Resource;
use watoki\curir\responder\Presenter;
use watoki\curir\Responder;
use watoki\curir\responder\Redirecter;

class AuthResource extends Resource {

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
            return Redirecter::fromString('..');
        }

        return new Presenter([
            'sent' => false,
            'login' => false,
        ]);
    }

    public function doPost(WebRequest $r, $email, $tokenOnly = false, $remember = false) {
        $token = $this->authentication->createToken($email);

        $expire = $this->config->then('5 minutes');
        list($challenge, $token) = $this->authentication->createChallenge($email, $token, $expire);
        $this->session->set('response', md5($token . $challenge));

        $this->sendEmail($r, $email, $token, $challenge, $tokenOnly, $remember);

        return new Presenter([
            'challenge' => ['value' => $challenge],
            'sent' => ['to' => $email],
            'email' => false,
            'login' => false,
        ]);
    }

    /**
     * @param WebRequest $request
     * @param string $response
     * @param bool $remember
     * @throws HttpError
     * @return Presenter
     */
    public function doLogin(WebRequest $request, $response, $remember = false) {
        try {
            list($userId, $token) = $this->authentication->validateResponse($response);
            $this->session->setLoggedIn($userId);

            if ($remember) {
                list($challenge, $newToken) = $this->authentication->createChallenge($userId, $token);
                $this->session->set('response', md5($newToken . $challenge));
            }
            return new Presenter([
                'challenge' => isset($challenge) ? ['value' => $challenge] : null,
                'sent' => false,
                'email' => false,
                'login' => ['target' => $request->getContext() . '/..'],
            ]);
        } catch (\Exception $e) {
            throw new HttpError(WebResponse::STATUS_UNAUTHORIZED, $e->getMessage());
        }
    }

    private function sendEmail(WebRequest $request, $email, $token, $challenge, $tokenOnly, $remember) {
        $content = '';

        if (!$tokenOnly) {
            $url = $request->getContext();
            $url->getParameters()->set('method', 'login');
            $url->getParameters()->set('response', md5($token . $challenge));
            $url->getParameters()->set('remember', print_r($remember, true));

            $url->setFragment($token);
            $content .= $url->toString() . "\n\n";
        }

        $content .= "Token: " . $token;

        $this->email->send($email, 'xkdl@rtens.org', 'xkdl login', $content);
    }

    public function doLogout(WebRequest $request) {
        $this->session->requireLoggedIn($request);
        $response = $this->session->get('response');
        $this->authentication->logout($response);
        $this->session->setLoggedIn(false);

        return Redirecter::fromString('..');
    }

} 