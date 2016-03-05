<?php
namespace Tests\Phlite\Logging;
use Phlite\Logging;

use Phlite\Logging\Registry;

class RegistryTest
extends \PHPUnit_Framework_TestCase {
    function testCascadedLogger() {
        $l = Registry::getLogger('a.b.c');
        $this->assertInstanceOf('Phlite\Logging\RootLogger', $l->getParent());
    }

    /**
     * @depends testCascadedLogger
     */
    function testPlaceholder() {
        // Ensure when creating a nested logger that place-holders are installed
        // up the dotted namespace chain
        $this->assertInstanceOf('Phlite\Logging\Placeholder',
            \Monolog\Registry::getInstance('a.b'));
        $this->assertInstanceOf('Phlite\Logging\Placeholder',
            \Monolog\Registry::getInstance('a'));
    }

    function testNamespaceLogger() {
        // Ensure the namespace is converted to lowercase and dotted (rather
        // than slashed)
        $l = Registry::getLogger(__NAMESPACE__, true);
        $this->assertEquals($l->getName(), strtolower(str_replace('\\', '.', __NAMESPACE__)));
    }
}
