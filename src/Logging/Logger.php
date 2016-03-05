<?php
namespace Phlite\Logging;

use Monolog;

/**
 * This is an adaptation of the Monolog logging system which follows the logic
 * of the Python logging interface. The
 */
class Logger
extends Monolog\Logger {
    var $level;
    var $disabled = 0; // Unused ?
    var $parent;
    var $propagate = 1;

    const NOTSET = 1000;

    function __construct($name, $level=self::NOTSET, array $handlers = array(),
        array $processors = array(), DateTimeZone $timezone = null
    ) {
        parent::__construct($name, $handlers, $processors, $timezone);
        $this->level = $this->_checkLevel($level);
    }

    function setLevel($level) {
        $this->level = $this->_checkLevel($level);
    }

    function getEffectiveLevel() {
        $logger = $this;
        while ($logger) {
            if ($logger->level) {
                return $logger->level;
            }
            $logger = $logger->parent;
        }
        return self::NOTSET;
    }

    static function _checkLevel($level) {
        if (is_int($level)) {
            $rv = $level;
        }
        elseif ((string) $level == $level) {
            $levels = static::getLevels();
            if (!isset($levels[$level]))
                throw new InvalidArgumentException(
                    sprintf('Unknown level: %s', $level));
            $rv = $levels[$level];
        }
        else {
            throw new InvalidArgumentException(
                sprintf('Level not an integer or a valid string: %s',
                    $level));
        }
        return $rv;
    }

    function isEnabledFor($level) {
        if (Registry::$disable >= $level)
            return 0;
        return $this->getEffectiveLevel() >= $level;
    }

    function addRecord($level, $message, array $context = array()) {
        if (!$this->disabled && $this->isEnabledFor($level))
            return $this->emit($level, $message, $context);
        return false;
    }

    /**
     * Emits a log record. This is an adaptation of Monolog\Logger::addRecord,
     * which is optimized to support the cascaded log structure represented
     * in Phlite's logging system. It also removes the automatic StreamHandler
     * for stderr and the short circuit logic when this logger does not have
     * and handlers.
     *
     * @param  int     $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function emit($level, $message, array $context = array())
    {
        $levelName = static::getLevelName($level);

        if (!static::$timezone) {
            static::$timezone = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }
        if ($this->microsecondTimestamps) {
            $ts = \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)), static::$timezone);
        } else {
            $ts = new \DateTime(null, static::$timezone);
        }
        $ts->setTimezone(static::$timezone);
        $record = array(
            'message' => ((string) $message),
            'context' => $context,
            'level' => $level,
            'level_name' => $levelName,
            'channel' => $this->name,
            'datetime' => $ts,
            'extra' => array(),
        );

        $l = $this;
        do {
            $handlers = $l->getHandlers();
            foreach ($l->getProcessors() as $processor) {
                $record = call_user_func($processor, $record);
            }
            while ($handler = current($handlers)) {
                if (true === $handler->handle($record)) {
                    break;
                }
                next($handlers);
            }
            if (!$l->propagate)
                break;
            $l = $l->getParent();
        } while ($l);
        return true;
    }

    /**
     * Get a logger which is a descendant to this one.
     *
     * This is a convenience method, such that
     *
     * logging.getLogger('abc').getChild('def.ghi')
     *
     * is the same as
     *
     * logging.getLogger('abc.def.ghi')
     *
     * It's useful, for example, when the parent logger is named using
     * __namespace__ rather than a literal string.
     */
    function getChild($suffix) {
        if (static::$root !== $this) {
            $suffix = $this->name . '.' . $suffix;
        }
        return Registry::getLogger($suffix);
    }

    function getParent() {
        return $this->parent;
    }
}
