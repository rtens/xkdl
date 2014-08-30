<?php
namespace rtens\xkdl\storage;

use rtens\xkdl\lib\Configuration;
use rtens\xkdl\lib\Schedule;
use rtens\xkdl\lib\Slot;
use rtens\xkdl\lib\TimeWindow;
use rtens\xkdl\Task;

class Writer {

    /** @var Configuration <- */
    public $config;

    public function addLog($fullTaskName, TimeWindow $window) {
        $data = $window->start->format('c') . ' >> ' . $window->end->format('c') . "\n";
        file_put_contents($this->getTaskFolder($fullTaskName) . '/logs.txt', $data, FILE_APPEND);
    }

    public function markDone($fullTaskName) {
        $this->prepend('x_', $fullTaskName);
    }

    public function markOpen($fullTaskName) {
        $this->prepend('__', $fullTaskName);
    }

    private function prepend($prefix, $fullTaskName) {
        $taskDirPath = $this->getTaskFolder($fullTaskName);
        $parentFolder = dirname($taskDirPath);
        $oldFolderName = basename($taskDirPath);
        $newFolderName = $oldFolderName;

        if (substr($newFolderName, 1, 1) == '_') {
            $newFolderName = substr($newFolderName, 2);
        }
        $newFolderName = $prefix . $newFolderName;

        rename($parentFolder . '/' . $oldFolderName, $parentFolder . '/' . $newFolderName);
    }

    private function getTaskFolder($fullTaskName) {
        $dir = $this->config->rootTaskFolder();

        foreach (explode('/', trim($fullTaskName, '/')) as $taskName) {
            foreach (glob($dir . '/*') as $file) {
                if (basename($file) == $taskName || preg_match('/[xX_]_(\d+_)?' . $taskName . '/', basename($file))) {
                    $dir = $file;
                    continue 2;
                }
            }
            $dir = $dir . '/' . $taskName;
            mkdir($dir);
        }
        return $dir;
    }

    public function startLogging($task, \DateTime $start) {
        file_put_contents($this->loggingFile(), $task . "\n" . $start->format('c'));
    }

    public function stopLogging(\DateTime $end) {
        $data = $this->readTmpFile();
        $this->addLog($data['task'], new TimeWindow($data['start'], $end));
        $this->cancelLogging();
        return $data['task'];
    }

    public function cancelLogging() {
        unlink($this->loggingFile());
    }

    /**
     * @return array|null|\DateTime[]|string[]
     */
    public function getOngoingLogInfo() {
        if (!file_exists($this->loggingFile())) {
            return null;
        }

        return $this->readTmpFile();
    }

    private function loggingFile() {
        return $this->config->homeFolder() . '/logging';
    }

    private function scheduleFile() {
        return $this->config->homeFolder() . '/schedule.txt';
    }

    private function scheduleArchiveFolder() {
        return $this->config->homeFolder() . '/schedules';
    }

    private function scheduleArchiveFile() {
        return $this->scheduleArchiveFolder() . '/' . $this->config->scheduleArchiveFileName();
    }

    /**
     * @return array with 'task' and 'start' keys
     */
    private function readTmpFile() {
        list($task, $start) = explode("\n", file_get_contents($this->loggingFile()));
        return array(
            'task' => $task,
            'start' => new \DateTime($start)
        );
    }

    public function saveSchedule(Schedule $schedule) {
        $content = $schedule->getFrom()->format('c') . ' >> ' . $schedule->getUntil()->format('c') . "\n";

        foreach ($schedule->getSlots() as $slot) {
            $content .= $slot->window->start->format('c') . ' >> ' .
                $slot->window->end->format('c') . ' >> ' .
                $slot->task->getFullName() . "\n";
        }

        if (!file_exists($this->scheduleArchiveFolder())) {
            mkdir($this->scheduleArchiveFolder(), 0777, true);
        }

        file_put_contents($this->scheduleArchiveFile(), $content);
        file_put_contents($this->scheduleFile(), $content);
    }

    public function readSchedule(Task $root) {
        if (!file_exists($this->scheduleFile())) {
            return new Schedule(new \DateTime(), new \DateTime());
        }

        $schedule = null;
        foreach (explode("\n", file_get_contents($this->scheduleFile())) as $i => $line) {
            if (!trim($line)) {
                continue;
            }

            if ($i == 0) {
                list($start, $end) = explode(" >> ", trim($line));
                $schedule = new Schedule(new \DateTime($start), new \DateTime($end));
            } else {
                list($start, $end, $task) = explode(" >> ", trim($line));
                $schedule->addSlot(new Slot($this->findTask($root, $task),
                    new TimeWindow(new \DateTime($start), new \DateTime($end))));
            }
        }
        return $schedule;
    }

    private function findTask(Task $parent, $fullName) {
        foreach (explode('/', trim($fullName, '/')) as $name) {
            $parent = $parent->getChild($name);
        }
        return $parent;
    }
}