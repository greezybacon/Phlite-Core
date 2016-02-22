<?php

namespace Phlite\Util;

abstract class BaseList
implements \Countable, \IteratorAggregate, \Serializable, \JsonSerializable {
    protected $storage = array();

    function __construct(/* Iterable */ $array=array()) {
        foreach ($array as $k=>$v)
            $this->storage[$k] = $v;
    }

    /**
     * Sort the list in place.
     *
     * Parameters:
     * $key - (callable|int) A callable function to produce the sort keys
     *      or one of the SORT_ constants used by the array_multisort
     *      function. The callable will receive both the value and the key as
     *      separate parameters.
     * $reverse - (bool) true if the list should be sorted descending
     */
    function sort($key=false, $reverse=false) {
        if (is_callable($key)) {
            $keys = array_map($key, $this->storage, $this->keys());
            array_multisort($keys, $reverse ? SORT_DESC : SORT_ASC,
                $this->storage);
        }
        elseif (is_array($key)) {
            array_multisort($key, $reverse ? SORT_DESC : SORT_ASC,
                $this->storage);
        }
        elseif ($key) {
            array_multisort($this->storage,
                $reverse ? SORT_DESC : SORT_ASC, $key);
        }
        else {
            array_multisort($this->storage, $reverse ? SORT_DESC : SORT_ASC);
        }
    }

    function ksort($reverse=false) {
        return $this->sort(function($v, $k) { return $k; }, $reverse);
    }

    function reverse() {
        return $this->sort(range(0, count($this->storage)-1), true);
    }

    function filter($callable) {
        return new static(array_filter($this->storage, $callable,
            ARRAY_FILTER_USE_BOTH));
    }

    // IteratorAggregate
    function getIterator() {
        return new \ArrayIterator($this->storage);
    }

    // Countable
    function count($mode=COUNT_NORMAL) {
        return count($this->storage, $mode);
    }

    // Serializable
    function serialize() {
        return serialize($this->storage);
    }
    function unserialize($what) {
        $this->storage = unserialize($what);
    }

    // JsonSerializable
    function jsonSerialize() {
        return $this->storage;
    }
}
