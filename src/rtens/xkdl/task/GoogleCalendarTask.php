<?php
namespace rtens\xkdl\task;

use rtens\xkdl\exception\AuthenticationException;
use rtens\xkdl\Task;

class GoogleCalendarTask extends Task {

    /** @var \Google_Client <- */
    public $client;

    public function getChildren() {
        throw new AuthenticationException($this->client->createAuthUrl());
    }

} 