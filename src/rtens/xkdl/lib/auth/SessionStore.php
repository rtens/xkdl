<?php
namespace rtens\xkdl\lib\auth;

use watoki\stores\file\FileStore;
use watoki\stores\file\SerializerRepository;

class SessionStore extends FileStore {

    public static $CLASS = __CLASS__;

    /**
     * @param SerializerRepository $serializers <-
     * @param SerializerRepository $root
     */
    public function __construct(SerializerRepository $serializers, $root) {
        parent::__construct(AuthenticatedSession::$CLASS, $serializers, $root);
    }

    /**
     * @param string $id
     * @return AuthenticatedSession
     */
    public function read($id) {
        return parent::read($id);
    }

} 