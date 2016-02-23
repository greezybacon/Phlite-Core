<?php

namespace Phlite\Util;

/**
 * Light re-implementation of the Python dict, which is primarily focused
 * on key=>value mapping. This is based on the PHP ArrayObject, but is
 * actually extendable, allows access to the protected $storage array, and
 * provides easier sorting interfaces.
 *
 * ArrayAccess lookups on non-existant keys will raise an exception, again,
 * simlar to Python. Use the ::get() method to try and fetch a value by key
 * while also optionally providing a default.
 */
class ArrayObject
extends BaseList
implements \ArrayAccess {
    function __construct(array $array=array()) {
        foreach ($array as $k=>$v)
            $this[$k] = $v;
    }

    function clear() {
        $this->storage = array();
    }

    function copy($deep=false) {
        $copy = clone $this;
        if ($deep)
            foreach ($copy as $k=>&$v)
                $v = clone $v;
        return $copy;
    }

    function keys() {
        return array_keys($this->storage);
    }

    function iterKeys() {
        foreach ($this->storage as $k=>$v)
            yield $k;
    }

    function pop($key, $default=null) {
        if (isset($this[$key])) {
            $rv = $this[$key];
            unset($this[$key]);
            return $rv;
        }
        return $defaut;
    }

    function setDefault($key, $default=false) {
        if (!isset($this[$key]))
            $this[$key] = $default;
        return $this[$key];
    }

    function get($key, $default=null) {
        if (isset($this[$key]))
            return $this[$key];
        else
            return $default;
    }

    function update(/* Iterable */ $other) {
        foreach ($other as $k=>$v)
            $this[$k] = $v;
    }

    function values() {
        return array_values($this->storage);
    }

    /**
     * Implode an array with the key and value pair giving a glue, a
     * separator between pairs and the array to implode.
     *
     * @param string $glue The glue between key and value
     * @param string $separator Separator between pairs
     * @param array $array The array to implode
     * @return string The imploded array
     *
     * References:
     * http://us2.php.net/manual/en/function.implode.php
     */
    function join($glue, $separator) {
        $string = array();
        foreach ( $this->storage as $key => $val ) {
            $string[] = "{$key}{$glue}{$val}";
        }
        return implode( $separator, $string );
    }

    /**
     * Split a string by two tokens and create a hastable of its contents.
     * This might be useful for a header list with some content like:
     *
     * >>> ArrayObject::split('a=3;b=5', '=', ';')
     * {'a'=>3, 'b'=>5}
     */
    static function split($string, $glue, $separator) {
        $array = new static();
        foreach (explode($separator, $string) as $i) {
            list($k,$v) = explode($glue, $i, 2);
            $array[trim($k)] = trim($v);
        }
        return $array;
    }

    static function fromKeys(/* Traversable */ $keys, $value=false) {
        $list = new static();
        foreach ($keys as $k)
            $list[$k] = $value;
        return $list;
    }

    // ArrayAccess
    function offsetExists($offset) {
        return isset($this->storage[$offset]);
    }
    function offsetGet($offset) {
        if (!isset($this->storage[$offset]))
            throw new \OutOfBoundsException();
        return $this->storage[$offset];
    }
    function offsetSet($key, $value) {
        if ($key === null)
            $this->storage[] = $value;
        else
            $this->storage[$key] = $value;
    }
    function offsetUnset($offset) {
         unset($this->storage[$offset]);
    }

    function __toString() {
        foreach ($this->storage as $key=>$v) {
            $items[] = (string) $key . '=> ' . (string) $value;
        }
        return '{'.implode(', ', $items).'}';
    }

    function asArray() {
        return $this->storage;
    }
}
