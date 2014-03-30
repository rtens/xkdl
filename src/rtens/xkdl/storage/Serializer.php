<?php
namespace rtens\xkdl\storage;

use rtens\xkdl\Task;

interface Serializer {

    /**
     * @param $object
     * @return array
     */
    public function serialize($object);

    /**
     * @param $folder
     * @param array $properties
     * @internal param \rtens\xkdl\Task $parent
     * @return object
     */
    public function inflate($folder, $properties);

} 