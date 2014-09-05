<?php
namespace rtens\xkdl\storage;

use rtens\xkdl\lib\Configuration;
use rtens\xkdl\Task;

class TaskStore {

    public static $CLASS = __CLASS__;

    const PROPERTY_FILE_NAME = '__.txt';

    /** @var Configuration <- */
    public $config;

    /** @var SerializerFactory <- */
    public $serializerFactory;

    /** @var Task|null */
    private $root;

    public function refresh() {
        $this->root = null;
        return $this;
    }

    public function getRoot() {
        if (!$this->root) {
            $this->root = $this->readTask($this->config->rootTaskFolder());
        }
        return $this->root;
    }

    public function getTask($path) {
        $task = $this->getRoot();
        foreach (array_filter(explode('/', $path)) as $name) {
            $task = $task->getChild($name);
        }
        return $task;
    }

    private function readTask($folder, Task $parent = null) {
        $properties = $this->readProperties($folder);
        if (!isset($properties['type'])) {
            $properties['type'] = Task::$CLASS;
        }

        $task = $this->inflateTask($folder, $properties);
        if ($parent) {
            $parent->addChild($task);
        }

        $this->readChildren($folder, $task);

        return $task;
    }

    private function readChildren($folder, $task) {
        foreach (glob($folder . '/*') as $file) {
            if (is_dir($file)) {
                $this->readTask($file, $task);
            }
        }
    }

    /**
     * @param $folder
     * @param $properties
     * @return Task
     */
    private function inflateTask($folder, $properties) {
        $properties = $this->getMetaInformation($folder, $properties);
        return $this->serializerFactory->getSerializerFor($properties['type'])
            ->inflate($folder, $properties);
    }

    private function readProperties($folder) {
        $file = $folder . '/' . self::PROPERTY_FILE_NAME;

        if (!file_exists($file)) {
            return array();
        }

        $properties = array();
        foreach (explode("\n", file_get_contents($file)) as $line) {
            if (!strpos($line, ':')) {
                continue;
            }
            list($property, $value) = explode(':', trim($line), 2);
            $properties[strtolower(trim($property))] = trim($value);
        }
        return $properties;
    }

    private function getMetaInformation($folder, $properties) {
        $fileName = basename($folder);
        $name = $fileName;

        $properties['done'] = (strtolower(substr($fileName, 0, 2)) == 'x_');

        if (in_array(strtolower(substr($fileName, 0, 2)), array('__', 'x_'))) {
            $name = substr($fileName, 2);
            if (!isset($properties['duration'])) {
                $properties['duration'] = $this->config->defaultDurationString();
            }
        }

        if (strpos($name, '_')) {
            list($priorityString, $name) = explode('_', $name, 2);
            if (is_numeric($priorityString) && !isset($properties['priority'])) {
                $properties['priority'] = intval($priorityString);
            }
        }

        if (!isset($properties['name'])) {
            $properties['name'] = utf8_encode($name);
        }

        return $properties;
    }

} 