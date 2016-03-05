<?php
use Phlite\Util;

class Thingy
extends Util\BaseList {
  function keys() { return array_keys($this->storage); }
  function values() { return array_values($this->storage); }
  function storage() { return $this->storage; }
}

class BaseListTest
extends PHPUnit_Framework_TestCase {
  function getKeyedList() {
      return new Thingy([
          'q' => 7, 'r' => 9,
          'a' => 1, 'n' => 11,
          'b' => 12, 'o' => 5,
          'B' => 3, 'l' => 19,
          'D' => 4, 'J' => 14,
      ]);
  }

  function getList() {
      return new Thingy($this->getKeyedList()->values());
  }

  function testSimpleSort() {
      $list = $this->getList();
      $list->sort();
      $this->assertEquals(1, $list->storage()[0]);
  }

  function testSimpleKeySort() {
      $list = $this->getKeyedList();
      $list->ksort();
      $keys = $list->keys();
      $this->assertEquals('B', array_shift($keys));
  }

  // Reverse
  function testReverseSort() {
      $list = $this->getList();
      $list->sort(false, true);
      $this->assertEquals(19, $list->storage()[0]);
  }

  function testReverseKeySort() {
      $list = $this->getKeyedList();
      $list->ksort(true);
      $keys = $list->keys();
      $this->assertEquals('r', array_shift($keys));
  }

  // Key callable
  function testCallableKeySort() {
      $list = $this->getList();
      $list->sort(function($i, $k) { return -$i + ord($k); });
      $this->assertEquals(19, $list->storage()[0]);
  }

  function testCallableKeyKeySort() {
      $list = $this->getKeyedList();
      $list->sort(function($i, $k) { return -$i + ord($k); });
      $this->assertEquals('J', $list->keys()[0]);
  }

  // Reverse -- does not mean reverse sort
  function testReverse() {
      $list = $this->getList();
      $list->reverse();
      $this->assertEquals(14, $list->storage()[0]);
  }

  function testReverseKeyed() {
      $list = $this->getKeyedList();
      $list->reverse();
      $this->assertEquals(14, $list->values()[0]);
      $this->assertEquals('J', $list->keys()[0]);
  }

  // Filtering
  function testFilter() {
      $list = $this->getList();
      $list = $list->filter(function ($v) { return $v < 6; });
      $this->assertEquals(4, count($list));
      $this->assertEquals(1, $list->values()[0]);
  }

  function testKeyedFilter() {
      $list = $this->getKeyedList();
      $list = $list->filter(function ($v, $k) { return $v < 6 || $k > 'a'; });
      $this->assertEquals(9, count($list));
      $this->assertEquals('q', $list->keys()[0]);
  }

  function testCount() {
      $list = $this->getList();
      $this->assertEquals($list->count(), count($list->storage()));
  }
}
