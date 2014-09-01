<?php
namespace rtens\xkdl\web;

use watoki\curir\http\Request;
use watoki\curir\resource\DynamicResource;
use watoki\tempan\Renderer;

class Presenter extends \watoki\curir\responder\Presenter {

    const COOKIE_TIMEOUT = 604800; // 7 days

    private $headers;

    function __construct(DynamicResource $resource, $viewModel = array(), $headers = array()) {
        parent::__construct($resource, $viewModel);
        $this->headers = $headers;
    }

    public function renderHtml($template) {
        $renderer = new Renderer($template);
        return $renderer->render($this->getModel());
    }

    public function renderJson() {
        return json_encode($this->getModel(), JSON_PRETTY_PRINT);
    }

    public function createResponse(Request $request) {
        $response = parent::createResponse($request);
        foreach ($this->headers as $key => $value) {
            $response->getHeaders()->set($key, $value);
        }
        return $response;
    }

} 