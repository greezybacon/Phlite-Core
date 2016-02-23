<?php
use Phlite\Util;

class CaseArrayTests
extends PHPUnit_Framework_TestCase {
  function getKeyedList() {
      return new Util\CaseArrayObject([
          'qA' => 7, 'rA' => 9,
          'aA' => 1, 'nA' => 11,
          'bA' => 12, 'oA' => 5,
          'BA' => 3, 'lA' => 19,
          'DA' => 4, 'JA' => 14,
      ]);
  }

  function testCaseLookup() {
      $list = $this->getKeyedList();
      $this->assertEquals(7, $list['qa']);
      $this->assertEquals(7, $list['QA']);
      $this->assertEquals(7, $list['Qa']);
      $this->assertEquals(7, $list['qA']);
  }

  function testCaseTransformation() {
      $list = $this->getKeyedList();
      $list['QA'] += 5;
      $this->assertEquals($list['qa'], 12);
      $T = $list->keys();
      $this->assertEquals('QA', array_pop($T));
  }

  function testKeyedSort() {
      $list = $this->getKeyedList();
      $list->ksort();
      $this->assertEquals(
          ['aA', 'BA', 'DA', 'JA', 'lA', 'nA', 'oA', 'qA', 'rA'],
          $list->keys());
  }

  function testCaseUnset() {
      $list = $this->getKeyedList();
      unset($list['AA']);
      $this->assertFalse(isset($list['aa']));
  }
}
