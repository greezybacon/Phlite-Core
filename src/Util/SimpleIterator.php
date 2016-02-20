<?php

namespace Phlite\Util;

/**
 * Simple iterator class which mimicks Python's iteration technique, utilizing
 * only a iter() method which returns an array of [key, value]. This class
 * adapts the Python method onto the PHP method for simplified iterator classes.
 */
abstract class SimpleIterator
implements Iterator {
    protected $__key;
    protected $__value;
    protected $__valid = true;

    abstract function iter();

    function rewind() {
        unset($this->__key);
        unset($this->__value);
        $this->next();
    }

    function valid() {
        return $this->__valid;
    }

    function next() {
        @list($this->__key, $this->__value) = $this->iter();
        $this->__valid = isset($this->__key);
    }

    function key() {
        return $this->__key;
    }

    function current() {
        return $this->__value;
    }
}
