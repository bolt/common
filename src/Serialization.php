<?php

namespace Bolt\Common;

use Bolt\Common\Exception\DumpException;
use Bolt\Common\Exception\ParseException;

/**
 * Wrapper around serialize()/unserialize().
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Serialization
{
    /**
     * Dump (Serialize) value.
     *
     * @param mixed $value
     *
     * @throws DumpException when serializing fails
     *
     * @return string
     */
    public static function dump($value)
    {
        try {
            return serialize($value);
        } catch (\Error $e) {
        } catch (\Exception $e) {
        }

        throw new DumpException(sprintf('Error serializing value. %s', $e->getMessage()), 0, $e);
    }

    /**
     * Parse (Unserialize) value.
     *
     * @param string $value
     * @param array  $options
     *
     * @throws ParseException when unserializing fails
     *
     * @return mixed
     */
    public static function parse($value, $options = [])
    {
        static $handler;
        if (!$handler) {
            $handler = function ($severity, $message, $file, $line) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            };
        }

        set_error_handler($handler);
        $unserializeHandler = ini_set('unserialize_callback_func', __CLASS__ . '::handleUnserializeCallback');
        try {
            if (PHP_VERSION_ID < 70000) {
                return unserialize($value);
            }

            return unserialize($value, $options);
        } catch (ParseException $e) {
            throw $e;
        } catch (\Error $e) {
        } catch (\Exception $e) {
        } finally {
            restore_error_handler();
            ini_set('unserialize_callback_func', $unserializeHandler);
        }

        throw new ParseException('Error parsing serialized value.', -1, null, 0, $e);
    }

    /**
     * @internal
     *
     * @param string $class
     */
    public static function handleUnserializeCallback($class)
    {
        throw new ParseException(sprintf('Error parsing serialized value. Could not find class: %s', $class));
    }
}
