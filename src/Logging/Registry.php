<?php
namespace Phlite\Logging;

use Monolog;

/**
 * There is the one Registry instance, which holds the hierarchy of loggers.
 * It provides the interface to create and fetch loggers from via a dotted
 * channel system.
 */
class Registry
extends Monolog\Registry {
    static $loggerClass = Logger::class;
    static $disable = Logger::NOTSET;
    static $root;

    /**
     * Get a logger with the specified name (channel name), creating it if
     * it doesn't yet exist. This name is a dot-separated hierarchical name,
     * such as "a", "a.b.", "a.b.c" or similar.
     *
     * If a PlaceHolder existed for the specified name [i.e. the logger
     * didn't exist but a child of it did], replace it with the created
     * logger and fix up the parent/child references which pointed to the
     * placeholder to now point to the logger.
     *
     * @param Boolean $ns Convert backslash to dot and to lower case
     */
    static function getLogger($name, $ns=false) {
        $rv = null;
        if (!is_string($name))
            throw new \InvalidArgumentException(
                'Logger names must be a string');
        if ($ns)
            $name = strtolower(str_replace('\\', '.', $name));
        if (static::hasLogger($name)) {
            $rv = parent::getInstance($name);
            if ($rv instanceof PlaceHolder) {
                $ph = $rv;
                $rv = new static::$loggerClass($name);
                static::addLogger($rv, $name, true);
                static::fixupChildren($ph, $rv);
                static::fixupParents($rv);
            }
        }
        else {
            $rv = new static::$loggerClass($name);
            static::addLogger($rv, $name);
            static::fixupParents($rv);
        }
        return $rv;
    }

    static function setLoggerClass($class) {
        if ($class != Logger::class) {
            if (!is_subclass_of($class, Logger::class)) {
                throw new \InvalidArgumentException(
                    'logger not derived from Phlite\Logging\Logger');
            }
        }
        static::$loggerClass = $class;
    }

    static function getLoggerClass() {
        return static::$loggerClass;
    }

    /**
     * Ensure that there are either loggers or placeholders all the way from
     * the specified logger to the root of the logger hierarchy.
     */
    protected static function fixupParents($alogger) {
        $substr = $alogger->getName();
        $i = strrpos($substr, ".");
        $rv = null;
        while ($i > 0 and !$rv) {
            $substr = substr($substr, 0, $i);
            if (!static::hasLogger($substr)) {
                static::addLogger(new PlaceHolder($alogger), $substr);
            }
            else {
                $obj = static::getLogger($substr);
                if ($obj instanceof Logger) {
                    $rv = $obj;
                }
                else {
                    $obj->append($alogger);
                }
            }
            $i = strrpos($substr, '.');
        }
        if (!$rv) {
            $rv = static::getRoot();
        }
        $alogger->parent = $rv;
    }

    /**
     * Ensure that children on the placeholder ph are connected to the
     * specified logger.
     */
    protected static function fixupChildren($ph, $alogger) {
        $name = $alogger->getName();
        $namelen = strlen($name);
        foreach ($ph->loggerMap as $c) {
            if (substr($c->parent->getName(), 0, $namelen) != $name) {
                $alogger->parent = $c->parent;
                $c->parent = $alogger;
            }
        }
    }

    static function getRoot() {
        if (!isset(static::$root))
            static::$root = new RootLogger();
        return static::$root;
    }
}
