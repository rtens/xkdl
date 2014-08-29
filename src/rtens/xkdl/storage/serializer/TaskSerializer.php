<?php
namespace rtens\xkdl\storage\serializer;

use rtens\xkdl\lib\ExecutionWindow;
use rtens\xkdl\lib\TimeSpan;
use rtens\xkdl\lib\TimeWindow;
use rtens\xkdl\storage\Serializer;
use rtens\xkdl\Task;

class TaskSerializer implements Serializer {

    /**
     * @param array $folder
     * @param array $properties
     * @internal param \rtens\xkdl\Task $parent
     * @return object
     */
    public function inflate($folder, $properties) {
        $duration = isset($properties['duration']) ? new TimeSpan($properties['duration']) : null;
        $task = $this->createInstance($properties['name'], $duration);

        $this->setProperties($task, $properties);
        $this->setWindows($task, $folder . '/windows.txt');
        $this->setLogs($task, $folder . '/logs.txt');
        $this->setDescription($task, $folder . '/description.txt');

        return $task;
    }

    protected function createInstance($name, TimeSpan $duration = null) {
        return new Task($name, $duration);
    }

    private function setProperties(Task $task, $properties) {
        foreach ($properties as $name => $value) {
            $this->setProperty($task, $name, $value);
        }
    }

    protected function setProperty(Task $task, $name, $value) {
        switch ($name) {
            case 'done':
                $task->setDone($value);
                break;
            case 'priority':
                $task->setPriority($value);
                break;
            case 'deadline':
                $task->setDeadline(new \DateTime($value));
                break;
            case 'duration':
                $task->setDuration(new TimeSpan($value));
                break;
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

    private function setDescription(Task $task, $file) {
        if (file_exists($file)) {
            $task->setDescription(file_get_contents($file));
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

    /**
     * @param $object
     * @return array
     */
    public function serialize($object) {

    }
}