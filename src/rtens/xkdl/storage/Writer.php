<?php
namespace rtens\xkdl\storage;

use rtens\xkdl\lib\TimeWindow;

class Writer {

    private $userFolder;

    public function __construct($userFolder = null) {
        $this->userFolder = $userFolder ?: ROOT . '/user';
    }

    public function addLog($fullTaskName, TimeWindow $window) {
        $dir = $this->userFolder . '/root';

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

        $data = $window->start->format('c') . ' >> ' . $window->end->format('c') . "\n";
        file_put_contents($dir . '/logs.txt', $data, FILE_APPEND);
    }

    public function startLogging($task, \DateTime $start) {
        file_put_contents($this->tmpFile(), $task . "\n" . $start->format('c'));
    }

    public function stopLogging(\DateTime $end) {
        $data = $this->readTmpFile();
        $this->addLog($data['task'], new TimeWindow($data['start'], $end));
        $this->cancelLogging();
    }

    public function cancelLogging() {
        unlink($this->tmpFile());
    }

    /**
     * @return array|null|\DateTime[]|string[]
     */
    public function getOngoingLogInfo() {
        if (!file_exists($this->tmpFile())) {
            return null;
        }

        return $this->readTmpFile();
    }

    private function tmpFile() {
        return $this->userFolder . '/logging';
    }

    /**
     * @return array
     */
    private function readTmpFile() {
        list($task, $start) = explode("\n", file_get_contents($this->tmpFile()));
        return array(
            'task' => $task,
            'start' => new \DateTime($start)
        );
    }
}