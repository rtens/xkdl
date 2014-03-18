<?php
namespace rtens\xkdl\storage;

use rtens\xkdl\lib\TimeWindow;
use rtens\xkdl\RepeatingTask;
use rtens\xkdl\Task;

class Reader {

    private $rootFolder;

    function __construct($rootFolder) {
        $this->rootFolder = $rootFolder;
    }

    public function read() {
        $root = new Task('root', 0);
        $this->readChildren($this->rootFolder, $root);
        return $root;
    }

    private function readChildren($folder, Task $parent) {
        foreach (glob($folder . '/*') as $file) {
            $fileName = basename($file);
            $name = substr($fileName, 2);

            if (is_dir($file) && substr($fileName, 1, 1) == '_') {
                $duration = 0;
                if (strpos($name, '_')) {
                    list($durationString, $name) = explode('_', $name, 2);
                    if (is_numeric($durationString)) {
                        $duration = floatval($durationString);
                    }
                }

                $properties = $this->readProperties($file . '/__.txt');

                if (array_key_exists('repeat', $properties)) {
                    $child = new RepeatingTask($name, $duration);
                    $child->repeatEvery(new \DateInterval($properties['repeat']));
                } else {
                    $child = new Task($name, $duration);
                }

                $child->setDone(strtolower(substr($fileName, 0, 1)) == 'x');
                $parent->addChild($child);

                $this->setProperties($child, $properties);
                $this->setWindows($child, $file . '/windows.txt');
                $this->setLogs($child, $file . '/logs.txt');

                $this->readChildren($file, $child);
            }
        }
    }

    private function readProperties($file) {
        if (!file_exists($file)) {
            return array();
        }

        $properties = array();
        foreach (explode("\n", file_get_contents($file)) as $line) {
            list($property, $value) = explode(':', trim($line), 2);
            $properties[strtolower(trim($property))] = trim($value);
        }
        return $properties;
    }

    private function setProperties(Task $task, $properties) {
        foreach ($properties as $name => $value) {
            switch ($name) {
                case 'deadline':
                    $task->setDeadline(new \DateTime($value));
            }
        }
    }

    private function setWindows(Task $task, $file) {
        foreach ($this->readWindows($file) as $window) {
            $task->addWindow($window);
        }
    }

    private function setLogs(Task $task, $file) {
        foreach ($this->readWindows($file) as $window) {
            $task->addLog($window);
        }
    }

    private function readWindows($file) {
        if (!file_exists($file)) {
            return array();
        }

        $windows = array();
        foreach (explode("\n", file_get_contents($file)) as $line) {
            list($start, $end) = explode('>>', $line);
            $windows[] = new TimeWindow(new \DateTime(trim($start)), new \DateTime(trim($end)));
        }
        return $windows;
    }
}