<?php
namespace rtens\xkdl\storage;

use rtens\xkdl\lib\Configuration;
use rtens\xkdl\lib\ExecutionWindow;
use rtens\xkdl\lib\TimeSpan;
use rtens\xkdl\lib\TimeWindow;
use rtens\xkdl\RepeatingTask;
use rtens\xkdl\Task;

class Reader {

    /** @var Configuration <- */
    public $config;

    public function read() {
        return $this->readTask($this->config->rootTaskFolder());
    }

    private function readTask($file, Task $parent = null) {
        $fileName = basename($file);
        $name = $fileName;
        $duration = null;
        $priority = null;

        if (in_array(strtolower(substr($fileName, 0, 2)), array('__', 'x_'))) {
            $name = substr($fileName, 2);
            $duration = $this->config->defaultDuration();
        }

        if (strpos($name, '_')) {
            list($priorityString, $name) = explode('_', $name, 2);
            if (is_numeric($priorityString)) {
                $priority = intval($priorityString);
            }
        }

        $properties = $this->readProperties($file . '/__.txt');

        if (array_key_exists('repeat', $properties)) {
            $child = new RepeatingTask($name, $duration);
            $child->repeatEvery(new \DateInterval($properties['repeat']));
        } else {
            $child = new Task($name, $duration);
        }

        if ($parent) {
            $parent->addChild($child);
        }
        if ($priority) {
            $child->setPriority($priority);
        }
        $child->setDone(strtolower(substr($fileName, 0, 2)) == 'x_');

        $this->setProperties($child, $properties);
        $this->setWindows($child, $file . '/windows.txt');
        $this->setLogs($child, $file . '/logs.txt');

        $this->readChildren($file, $child);

        return $child;
    }

    private function readChildren($folder, Task $parent) {
        foreach (glob($folder . '/*') as $file) {
            if (is_dir($file)) {
                $this->readTask($file, $parent);
            }
        }
    }

    private function readProperties($file) {
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

    private function setProperties(Task $task, $properties) {
        foreach ($properties as $name => $value) {
            switch ($name) {
                case 'deadline':
                    $task->setDeadline(new \DateTime($value));
                    break;
                case 'duration':
                    $task->setDuration(new TimeSpan($value));
                    break;
            }
        }
    }

    private function setWindows(Task $task, $file) {
        foreach ($this->readWindows($file) as $window) {
            $task->addWindow(new ExecutionWindow($window->start, $window->end));
        }
    }

    private function setLogs(Task $task, $file) {
        foreach ($this->readWindows($file) as $window) {
            $task->addLog($window);
        }
    }

    /**
     * @param $file
     * @throws \Exception
     * @return array|TimeWindow[]
     */
    private function readWindows($file) {
        if (!file_exists($file)) {
            return array();
        }

        $windows = array();
        foreach (explode("\n", file_get_contents($file)) as $i => $line) {
            if (!trim($line)) {
                continue;
            }
            if (!strpos($line, '>>')) {
                throw new \Exception("Wrong format in [$file] line [" . ($i + 1) . "]");
            }
            list($start, $end) = explode('>>', $line);
            $windows[] = new TimeWindow(new \DateTime(trim($start)), new \DateTime(trim($end)));
        }
        return $windows;
    }
}