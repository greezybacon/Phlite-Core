<?php

class HelloSignal
extends Phlite\Signal {}

class SignalTest
extends PHPUnit_Framework_TestCase {
    function testSignalRegister() {
        $sent = false;
        Phlite\Signal::connect('test', null, function() use (&$sent) { $sent = true; });
        Phlite\Signal::send('test');
        $this->assertTrue($sent);
    }

    function testSubclass() {
        $sent = false;
        HelloSignal::connect(function() use (&$sent) { $sent = true; });
        HelloSignal::send(null);
        $this->assertTrue($sent);
    }
}
