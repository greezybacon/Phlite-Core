<?php
use Phlite\Util;

class ArrayObjectTest
extends PHPUnit_Framework_TestCase {
  function getKeyedList() {
      return new Util\ArrayObject([
          'qA' => 7, 'rA' => 9,
          'aA' => 1, 'nA' => 11,
          'bA' => 12, 'oA' => 5,
          'BA' => 3, 'lA' => 19,
          'DA' => 4, 'JA' => 14,
      ]);
  }

  function getNumericList() {
      return new Util\ArrayObject([
          1 => 56, 13 => 78,
          5 => 40, 2 => 42,
          19 => 66, 7 => 11,
          9 => 17, 3 => 31,
      ]);
  }

  function getMixedList() {
      $base = $this->getKeyedList();
      $base->update($this->getNumericList());
      return $base;
  }

  function testArrayAccess() {
      $list = $this->getMixedList();
      $this->assertArrayHasKey(1, $list);
      $this->assertArrayHasKey('JA', $list);
  }

  function testSetDefault() {
      $list = $this->getMixedList();
      $list->setDefault('__', 99);
      $list->setDefault(23, 45);
      $this->assertArrayHasKey(23, $list);
      $this->assertArrayHasKey('__', $list);
  }

  function testGetDefault() {
      $list = $this->getMixedList();
      $this->assertNull($list->get('yy'));
      $this->assertEquals(23, $list->get('yy', 23));
  }

  function testUpdate() {
      $list = $this->getNumericList();
      $list->update([6 => 33, 7 => 12]);
      $this->assertEquals(12, $list[7]);
      $this->assertEquals(33, $list[6]);
  }

  function testAsArray() {
      $list = $this->getMixedList();
      $this->assertArraySubset($list->asArray(), $list);
  }

  function testFromKeys() {
      $list = Util\ArrayObject::fromKeys(['a', 'b', 'c'], 1);
      $this->assertEquals(['a'=>1, 'b'=>1, 'c'=>1], $list->asArray());

      $list = Util\ArrayObject::fromKeys($list->iterKeys(), 2);
      $this->assertEquals(['a'=>2, 'b'=>2, 'c'=>2], $list->asArray());
  }

  function testArrayAppend() {
      $list = new Util\ArrayObject();
      $list[] = 1;
      $list[] = 2;
      $this->assertEquals([1,2], $list->asArray());
  }
}
