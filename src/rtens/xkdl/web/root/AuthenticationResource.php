<?php
namespace rtens\xkdl\web\root;

use rtens\xkdl\lib\auth\Authenticator;
use rtens\xkdl\lib\auth\AuthenticatedSession;
use rtens\xkdl\lib\EmailSender;
use rtens\xkdl\lib\Time;
use watoki\curir\cookie\Cookie;
use watoki\curir\cookie\CookieStore;
use watoki\curir\delivery\WebRequest;
use watoki\curir\protocol\Url;
use watoki\curir\Resource;
use watoki\curir\responder\MultiResponder;
use watoki\curir\responder\Presenter;
use watoki\curir\Responder;

class AuthenticationResource extends Resource {

    /** @var Authenticator <- */
    public $authenticator;

    /** @var EmailSender <- */
    public $email;

    /** @var CookieStore <- */
    public $cookie;

    /** @var Time <- */
    public $time;

    public function after(Responder $responder, WebRequest $request) {
        if ($responder instanceof Presenter) {
            $responder = new Presenter(array_merge($responder->getModel(), [
                'target' => ['value' => Url::fromString($request->getContext() . '/..')->toString()]
            ]));
        }
        return parent::after($responder, $request);
    }

    /**
     * Shows form to enter email
     *
     * @param \watoki\curir\delivery\WebRequest $request <-
     * @return Presenter
     */
    public function doGet(WebRequest $request) {
        if ($request->getFormats()->contains('js')) {
            return new MultiResponder(file_get_contents(__DIR__ . '/authentication.js'));
        }
        if (!$request->getFormats()->contains('html')) {
            return new MultiResponder('not');
        }
        $challenge = null;
        try {
            $challenge = $this->authenticator->renew($this->cookie->read('session'));
        } catch (\Exception $e) {}

        return new Presenter([
            'email' => true,
            'logout' => false,
            'sent' => null,
            'challenge' => $challenge ? ['value' => $challenge] : null
        ]);
    }

    /**
     * Creates a new Session for this user, sends seed token by email
     *
     * @param string $email
     * @return Presenter
     */
    public function doPost($email) {
        $session = $this->authenticator->create($email);
        list($seed, $challenge) = $this->authenticator->initialize($session);
        $this->email->send($email, 'xkdl@rtens.org', 'Your Token', $seed);

        $sessionId = $this->authenticator->getId($session);
        $cookie = new Cookie($sessionId, $this->time->then('7 days'));
        $this->cookie->create($cookie, 'session');

        return new Presenter([
            'email' => false,
            'logout' => false,
            'sent' => ['to' => $email],
            'challenge' => ['value' => $challenge]
        ]);
    }

    /**
     * Destroys the current session and deletes token in client
     *
     * @param AuthenticatedSession $session <-
     * @return Presenter
     */
    public function doDelete(AuthenticatedSession $session) {
        $this->authenticator->destroy($session);
        try {
            $this->cookie->delete($this->cookie->read('session'));
        } catch (\Exception $e) {}

        return new Presenter([
            'email' => false,
            'logout' => true,
            'sent' => null,
            'challenge' => null
        ]);
    }

} 