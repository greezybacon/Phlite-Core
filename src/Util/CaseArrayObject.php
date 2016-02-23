<?php

namespace Phlite\Util;

/**
 * Similar to the ArrayObject, except that string keys are stored and
 * compared case insensitively. It will also allow varying forms of Unicode
 * normalization if the intl extension is installed.
 *
 * Updates performed on keys considered the same will result in the data
 * being replaced for the simlar key. The key is also updated so that the
 * last set key will become the current key.
 *
 * This is performed by keeping a separate list of keys. This other list is
 * indexed by the upper case, unicode normalized version.
 *
 * >>> $q = new CaseArrayObject(['abc' => 123]);
 * >>> $q['ABc'] += 5
 * >>> $q
 * {'ABc'=> 128}
 */
class CaseArrayObject
extends ArrayObject {
    protected $keys = array();
    
    protected function convertKey($key) {
        static $hasIntl, $hasMbstring;
        if (!isset($hasIntl)) {
            $hasIntl = extension_loaded('intl');
            $hasMbstring = extension_loaded('mbstring');
        }
        
        if ($hasIntl)
            $key = normalizer_normalize($key, \Normalizer::FORM_D);
        
        if ($hasMbstring)
            $key = mb_strtoupper($key);
        else
            $key = strtoupper($key);
        
        return $key;
    }
    
    // Provide collated sort functionality
    function ksort($reverse=false) {
        $lk = array_flip($this->keys);
        return parent::sort(function($v,$k) use ($lk) { return $lk[$k]; },
            $reverse);
    }
    
    // ArrayAccess
    function offsetExists($key) {
        $K = (is_string($key)) ? $this->convertKey($key) : $key;
        return isset($this->keys[$K]);
    }
    function offsetGet($key) {
        $K = (is_string($key)) ? $this->convertKey($key) : $key;
        if (!isset($this->keys[$K]))
            throw new \OutOfBoundsException();
        $key = $this->keys[$K];
        return $this->storage[$key];
    }
    function offsetSet($key, $value) { 
        $K = (is_string($key)) ? $this->convertKey($key) : $key;
        // Store the collated key in $keys for lookup between
        if (isset($this->keys[$K]) && ($key != $this->keys[$K]))
            // Drop the old key if the new key is similar but not equivalent
            // so a new key is not added
            unset($this->storage[$this->keys[$K]]);
        $this->keys[$K] = $key;
        $this->storage[$key] = $value;
    }
    function offsetUnset($key) {
        $K = (is_string($key)) ? $this->convertKey($key) : $key;
        $key = $this->keys[$K];
        unset($this->keys[$K]);
        unset($this->storage[$key]);
    }
}