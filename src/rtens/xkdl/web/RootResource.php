<?php
namespace rtens\xkdl\web;

use rtens\xkdl\exception\AuthenticationException;
use rtens\xkdl\lib\auth\Authenticator;
use rtens\xkdl\lib\auth\InvalidSessionException;
use rtens\xkdl\lib\auth\AuthenticatedSession;
use rtens\xkdl\lib\AuthenticationService;
use rtens\xkdl\lib\Configuration;
use watoki\cfg\Loader;
use watoki\curir\Container;
use watoki\curir\cookie\CookieStore;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\protocol\MimeTypes;
use watoki\curir\responder\Redirecter;
use watoki\deli\Request;
use watoki\factory\providers\CallbackProvider;

class RootResource extends Container {

    public static $CLASS = __CLASS__;

    /** @var \Google_Client <- */
    public $client;

    /** @var AuthenticationService <- */
    public $authentication;

    /** @var Configuration <- */
    public $config;

    /** @var Loader <- */
    public $loader;

    /** @var CookieStore <- */
    public $cookie;

    /** @var Authenticator <- */
    public $authenticator;

    /**
     * @param Request|WebRequest $request
     * @return WebResponse
     */
    public function respond(Request $request) {
//        if ($this->session->has('token')) {
//            $this->client->setAccessToken($this->session->get('token'));
//        }

        $response = null;
        try {
            $response = $this->cookie->read('response');
            $sessionId = $this->cookie->read('session')->payload;

            $this->factory->setProvider(AuthenticatedSession::$CLASS, new CallbackProvider(function () use ($sessionId, $response) {
                return $this->authenticator->authenticate($sessionId, $response->payload);
            }));
        } catch (\Exception $e) {
        }

        try {
            /** @var AuthenticatedSession $session */
            $session = $this->factory->getInstance(AuthenticatedSession::$CLASS);
            $userId = $session->getUserId();
            $homeConfigFile = $this->config->userFolder() . '/home/' . $userId . '/HomeConfiguration.php';
            if (!file_exists($homeConfigFile)) {
                mkdir(dirname($homeConfigFile), 0777, true);
                file_put_contents($homeConfigFile,
                    '<?php namespace rtens\xkdl\lib; class HomeConfiguration extends UserConfiguration {}');
            }
            $this->loader->loadConfiguration(Configuration::$CLASS, $homeConfigFile,
                [$this->config->getRoot(), $userId]);
        } catch (InvalidSessionException $e) {
        }

        try {
            $response = parent::respond($request);
            if (isset($session) && isset($sessionId) && $response->getHeaders()->get(WebResponse::HEADER_CONTENT_TYPE) == MimeTypes::getType('html')) {
                $challenge = $this->authenticator->renew($sessionId);

                $response->setBody($response->getBody() . '
                <script src="http://crypto-js.googlecode.com/svn/tags/3.1.2/build/rollups/md5.js"></script>
                <script src="' . $request->getContext() .'/authentication.js"></script>
                <script language="JavaScript">
                    authentication.respond("' . $challenge .  '");
                </script>');
            }
            return $response;
        } catch (AuthenticationException $ae) {
            return $this->createResponse(Redirecter::fromString($ae->getAuthenticationUrl()), $request);
        } catch (InvalidSessionException $invalid) {
            if ($response) {
                $this->cookie->delete($response);
            }
            return $this->createResponse(Redirecter::fromString('authentication'), $request);
        }
    }

    public function doGet() {
        return Redirecter::fromString('schedule');
    }

    public function doAuthenticate($code) {
//        $this->client->authenticate($code);
//        $this->session->set('token', $this->client->getAccessToken());
//        return Redirecter::fromString('');
    }

} 