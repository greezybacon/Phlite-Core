<?php
namespace Phlite\Io;

interface InputStream {

    function read($size=0);
    function readline();
    function readlines();

    function seek($offset, $whence=false);
    function tell();

    function close();
}
