<?php

declare(strict_types=1);

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
     * @throws DumpException when serializing fails
     */
    public static function dump($value): string
    {
        try {
            return serialize($value);
        } catch (\Error $e) {
        } catch (\Throwable $e) {
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
     */
    public static function parse($value, $options = [])
    {
        $unserializeHandler = ini_set('unserialize_callback_func', self::class . '::handleUnserializeCallback');
        try {
            if (\PHP_VERSION_ID < 70000) {
                return Thrower::call('unserialize', $value);
            }

            return Thrower::call('unserialize', $value, $options);
        } catch (ParseException $e) {
            throw $e;
        } catch (\Error $e) {
        } catch (\Throwable $e) {
        } finally {
            ini_set('unserialize_callback_func', $unserializeHandler);
        }

        throw new ParseException('Error parsing serialized value.', -1, null, 0, $e);
    }

    /**
     * @internal
     *
     * @param string $class
     */
    public static function handleUnserializeCallback($class): void
    {
        throw new ParseException(sprintf('Error parsing serialized value. Could not find class: %s', $class));
    }
}
