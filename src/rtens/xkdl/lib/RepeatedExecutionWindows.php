<?php
namespace rtens\xkdl\lib;

class RepeatedExecutionWindows implements \ArrayAccess, \Iterator {

    /** @var array|ExecutionWindow[] */
    private $windows;

    /** @var \DateInterval */
    private $interval;

    /** @var int for iterator */
    private $offset = 0;

    public function __construct(\DateInterval $interval, $windows = array()) {
        $this->windows = $windows;
        $this->interval = $interval;
    }

    public function offsetExists($offset) {
        return true;
    }

    public function offsetGet($offset) {
        $repetition = intval($offset / count($this->windows));
        $index = $offset % count($this->windows);
        $window = $this->windows[$index];

        $repeatedWindow = new ExecutionWindow(clone $window->start, clone $window->end, $window->quota);
        for ($i = 0; $i < $repetition; $i++) {
            $repeatedWindow->start->add($this->interval);
            $repeatedWindow->end->add($this->interval);
        }
        return $repeatedWindow;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->windows[] = $value;
        } else {
            $this->windows[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        unset($this->windows[$offset]);
    }

    /**
     * Return the current element
     * @return mixed Can return any type.
     */
    public function current() {
        return $this->offsetGet($this->offset);
    }

    /**
     * Move forward to next element
     * @return void Any returned value is ignored.
     */
    public function next() {
        $this->offset++;
    }

    /**
     * Return the key of the current element
     * @return mixed scalar on success, or null on failure.
     */
    public function key() {
        return $this->offset;
    }

    /**
     * Checks if current position is valid
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid() {
        return true;
    }

    /**
     * Rewind the Iterator to the first element
     * @return void Any returned value is ignored.
     */
    public function rewind() {
        $this->offset = 0;
    }
}