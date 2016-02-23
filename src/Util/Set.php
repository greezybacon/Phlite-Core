<?php

namespace Phlite\Util;

/**
 * Implementation of the mathematical set.
 */
class Set
implements \IteratorAggregate, \ArrayAccess, \Serializable, \Countable {
    protected $storage = array();

    function __construct(array $array=array()) {
        if (count($array))
            $this->addAll($array);
    }

    function clear() {
        $this->storage = array();
    }

    function copy() {
        return clone $this;
    }

    function add($what) {
        $this->storage[$what] = 1;
    }
    function addAll(/* Iterable */ $other) {
        foreach ($other as $k)
            $this->add($k);
    }

    function contains($value) {
        return isset($this->storage[$value]);
    }

    function remove($value) {
        unset($this->storage[$value]);
    }

    // Set operations
    function intersect($other) {
        $intersection = array();
        foreach ($other as $k) {
            if (isset($this[$k]))
                $intersection[] = $k;
        }
        return new static($intersection);
    }
    function union($other) {
        $union = clone $this;
        foreach ($other as $k)
            $union->add($k);
        return $union;
    }

    // Countable
    function count() { return count($this->storage); }

    // IteratorAggregate
    function getIterator() {
        return new \ArrayIterator(array_keys($this->storage));
    }

    // ArrayAccess
    function offsetExists($offset) {
        return isset($this->storage[$offset]);
    }
    function offsetGet($offset) {
        throw new \Exception('Set entries do not have value');
    }
    function offsetSet($offset, $value) {
        throw new \Exception('Set entries do not have value');
    }
    function offsetUnset($offset) {
         unset($this->storage[$offset]);
    }

    // Serializable
    function serialize() {
        return serialize(array_keys($this->storage));
    }
    function unserialize($what) {
        $this->clear();
        $this->storage = array_fill_keys($what, 1);
    }

    function __toString() {
        return '{'.implode(', ', array_keys($this->storage)).'}';
    }
}
