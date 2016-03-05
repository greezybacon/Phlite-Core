<?php
namespace Phlite\Logging;

use Monolog;

/**
 * A root logger is not that different to any other logger, except that it
 * must have a logging level and there is only one instance of it in the
 * hierarchy.
 */
class RootLogger
extends Logger {
    function __construct() {
        parent::__construct("root");
    }
}
