<?php
namespace rtens\xkdl\storage;

use rtens\xkdl\task\RepeatingTask;
use rtens\xkdl\storage\serializer\RepeatingTaskSerializer;
use rtens\xkdl\storage\serializer\TaskSerializer;
use rtens\xkdl\Task;
use watoki\factory\Factory;

class SerializerFactory {

    /** @var Serializer[] */
    private $serializers = array();

    function __construct(Factory $factory) {
        $factory->setSingleton(get_class($this), $this);

        $this->register(Task::$CLASS, new TaskSerializer());
        $this->register(RepeatingTask::$CLASS, new RepeatingTaskSerializer());
    }

    public function register($class, Serializer $serializer) {
        $this->serializers[$class] = $serializer;
    }

    /**
     * @param $class
     * @throws \Exception If no Serializer is registered for given class
     * @return Serializer
     */
    public function getSerializerFor($class) {
        if (!isset($this->serializers[$class])) {
            throw new \Exception("Cannot inflate [$class]. No Serializer registered.");
        }

        return $this->serializers[$class];
    }
}