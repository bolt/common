<?php

namespace Bolt\Common;

/**
 * Wrapper around ini_get()/ini_set().
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Ini
{
    /** @var array [string key => bool editable] */
    private static $keys;

    /**
     * Checks whether the given key exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public static function has($key)
    {
        if (static::$keys === null) {
            static::readKeys();
        }

        return array_key_exists($key, static::$keys);
    }

    /**
     * Returns the value of the given key or the given default if it does not exist.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $value = ini_get($key);

        return $value === false ? $default : $value;
    }

    /**
     * Returns the value of the given key filtered to a boolean or the given default if it does not exist.
     *
     * @param string    $key
     * @param bool|null $default
     *
     * @return bool|null
     */
    public static function getBool($key, $default = null)
    {
        $value = static::get($key, $default);

        return $value !== null ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : null;
    }

    /**
     * Returns the value of the given key filtered to an int or float or the given default if it does not exist.
     *
     * @param string         $key
     * @param int|float|null $default
     *
     * @return int|float|null
     */
    public static function getNumeric($key, $default = null)
    {
        $value = static::get($key, $default);

        return $value !== null ? $value + 0 : null;
    }

    /**
     * Set a new value for the given key.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @throws \InvalidArgumentException when the value is not scalar or null.
     * @throws \RuntimeException when the key does not exist, it is not editable, or some unknown reason.
     */
    public static function set($key, $value)
    {
        Assert::nullOrScalar($value, 'ini values must be scalar or null. Got: %s');

        $ex = null;
        set_error_handler(function ($severity, $message, $file, $line) use (&$ex) {
            $ex = new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $result = ini_set($key, $value === false ? '0' : $value);
        } finally {
            restore_error_handler();
        }

        if ($result === false || $ex !== null) {
            if (!static::has($key)) {
                throw new \RuntimeException(sprintf('The ini option "%s" does not exist. New ini options cannot be added.', $key), 0, $ex);
            }
            if (!static::$keys[$key]) {
                throw new \RuntimeException(sprintf('Unable to change ini option "%s", because it is not editable at runtime.', $key, $value), 0, $ex);
            }

            $value = Assert::valueToString($value);
            throw new \RuntimeException(sprintf('Unable to change ini option "%s" to %s.', $key, $value), 0, $ex);
        }
    }

    /**
     * Process all ini options to get list of keys and determine which ones are editable.
     */
    private static function readKeys()
    {
        static::$keys = [];

        foreach (ini_get_all() as $key => $value) {
            static::$keys[$key] = $value['access'] === 1 /* user */ || $value['access'] === 7 /* all */;
        }
    }

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
