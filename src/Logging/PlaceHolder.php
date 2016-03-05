<?php
namespace Phlite\Logging;

use Monolog;

/**
 * PlaceHolder instaces are used in the Registry logger hierarchy to take the
 * place of nodes for which no loggers have been defined. This class is
 * intended for internal use only and not as part of the public API
 */
class PlaceHolder
extends Monolog\Logger {
    var $loggerMap;

    function __construct($alogger) {
        $this->loggerMap = array( spl_object_hash($alogger) => $alogger );
    }

    function append(PlaceHolder $alogger) {
        $key = spl_object_hash($alogger);
        if (!isset($this->loggerMap[$key])) {
            $this->loggerMap[$key] = $alogger;
        }
    }
}
