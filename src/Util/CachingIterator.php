<?php

namespace Phlite\Util;

abstract class CachingIterator
extends SimpleIterator
implements \ArrayAccess, \Countable {
    protected $__cache = array();
    protected $__i = 0;
    protected $__closed = false;

    function getCache() {
        return $this->__cache;
    }

    function rewind() {
        $this->__i = 0;
        parent::rewind();
    }

    function next() {
        $this->__i++;
        if (!$this->__closed) {
            parent::next();
            if ($this->__valid) {
                $this->__cache[] = array($this->__key, $this->__value);
            }
            else {
                // You have reached the end of the internet...
                $this->__closed = true;
            }
        }
    }

    function key() {
        return @$this->__cache[$this->__i][0];
    }

    function current() {
        return @$this->__cache[$this->__i][1];
    }

    function valid() {
        return count($this->__cache) >= $this->__i;
    }

    protected function fillTo($offs) {
        while (count($this->__cache) < $offs && $this->valid())
            $this->next();
    }

    // ArrayAccess interface
    function offsetGet($offs) {
        if (!$this->offsetExists($offs))
            throw new \Exception($offs.': Does not exist in this iterator');
        return $this->__cache[$offs][1];
    }

    function offsetExists($offs) {
        $this->fillTo($offs);
        return count($this->__cache) > $offs;
    }

    function offsetSet($offs, $value) {
        throw new \Exception('Iterator is read-only');
    }
    function offsetUnset($offs) {
        throw new \Exception('Iterator is read-only');
    }

    // Countable interface
    function count() {
        return count($this->asArray());
    }
}
