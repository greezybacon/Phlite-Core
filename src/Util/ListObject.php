<?php

namespace Phlite\Util;

/**
 * Lightweight implementation of the Python list in PHP. This allows for
 * treating an array like a simple list of items. The numeric indexes are
 * automatically updated so that the indeces of the list will always be from
 * zero and increasing positively.
 *
 * Negative indexes are supported which reference from the end of the list.
 * Therefore $queue[-1] will refer to the last item in the list.
 */
class ListObject
extends BaseList
implements \ArrayAccess {

    function __construct(/* Iterable */ $array=array()) {
        foreach ($array as $v)
            $this->storage[] = $v;
    }

    function append($what) {
        $this->storage[] = $what;
    }

    function extend($iterable) {
        if (is_array($iterable)) {
            $this->storage = array_merge($this->storage, $iterable);
        }
        else {
            foreach ($iterable as $v)
                $this->storage[] = $v;
        }
    }

    function insert($i, $value) {
        array_splice($this->storage, $i, 0, array($value));
    }

    function remove($value) {
        if (!($k = $this->index($value)))
            throw new \OutOfRangeException('No such item in the list');
        unset($this->storage[$k]);
    }

    function pop($at=false) {
        if ($at === false)
            return array_pop($this->storage);
        elseif (!isset($this->storage[$at]))
            throw new \OutOfRangeException('Index out of range');
        else {
            $rv = array_splice($this->storage, $at, 1);
            return $rv[0];
        }
    }

    function slice($offset, $length=null) {
        return array_slice($this->storage, $offset, $length);
    }

    function splice($offset, $length=0, $replacement=null) {
        return array_splice($this->storage, $offset, $length, $replacement);
    }

    function index($value) {
        return array_search($this->storage, $value);
    }

    function join($glue) {
        return implode($glue, $this->storage);
    }

    // ArrayAccess
    function offsetGet($offset) {
        if (!is_int($offset))
            throw new \InvalidArgumentException('List indices should be integers');
        elseif ($offset < 0)
            $offset += count($this->storage);
        if (!isset($this->storage[$offset]))
            throw new \OutOfBoundsException('List index out of range');
        return $this->storage[$offset];
    }
    function offsetSet($offset, $value) {
        if ($offset === null)
            return $this->storage[] = $value;
        elseif (!is_int($offset))
            throw new \InvalidArgumentException('List indices should be integers');
        elseif ($offset < 0)
            $offset += count($this->storage);
        if (!isset($this->storage[$offset]))
            throw new \OutOfBoundsException('List assignment out of range');
        $this->storage[$offset] = $value;
    }
    function offsetExists($offset) {
        if (!is_int($offset))
            throw new \InvalidArgumentException('List indices should be integers');
        elseif ($offset < 0)
            $offset += count($this->storage);
        return isset($this->storage[$offset]);
    }
    function offsetUnset($offset) {
        if (!is_int($offset))
            throw new \InvalidArgumentException('List indices should be integers');
        elseif ($offset < 0)
            $offset += count($this->storage);
        return $this->pop($offset);
    }

    function __toString() {
        return '['.implode(', ', $this->storage).']';
    }
}
