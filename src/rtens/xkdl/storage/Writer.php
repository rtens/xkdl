<?php
namespace rtens\xkdl\storage;

use rtens\xkdl\lib\TimeWindow;

class Writer {

    public function addLog($task, TimeWindow $window) {
        $dir = ROOT . '/user/root/' . $task;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $data = $window->start->format('c') . ' >> ' . $window->end->format('c') . "\n";
        file_put_contents($dir . '/logs.txt', $data, FILE_APPEND);
    }

    public function startLogging($task, \DateTime $start) {
        file_put_contents($this->tmpFile(), $task . "\n" . $start->format('c'));
    }

    public function stopLogging(\DateTime $end) {
        $data = $this->readTmpFile();
        $this->addLog($data['task'], new TimeWindow(new \DateTime($data['start']), $end));
        unlink($this->tmpFile());
    }

    public function isLogging() {
        if (!file_exists($this->tmpFile())) {
            return null;
        }

        return $this->readTmpFile();
    }

    private function tmpFile() {
        return ROOT . '/user/logging';
    }

    /**
     * @return array
     */
    private function readTmpFile() {
        list($task, $start) = explode("\n", file_get_contents($this->tmpFile()));
        return array(
            'task' => $task,
            'start' => $start
        );
    }
}