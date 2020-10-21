<?php

declare(strict_types=1);

namespace Bolt\Common;

/**
 * Temporarily set PHP error reporting to throw ErrorExceptions.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Thrower
{
    /**
     * @var callable
     */
    private static $handler;

    /** @noinspection PhpDocSignatureInspection */

    /**
     * Call the given callable with given args, but throws an ErrorException when an error/warning/notice is triggered.
     *
     * @throws \ErrorException when an error/warning/notice is triggered
     */
    public static function call(callable $callable)
    {
        static::set();
        try {
            return call_user_func_array($callable, array_slice(func_get_args(), 1));
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Set the error handler to throw \ErrorExceptions (excluding deprecated warnings).
     *
     * To revert call {@see restore_error_handler}.
     *
     * @return callable|null the previous handler
     */
    public static function set(): ?callable
    {
        if (! static::$handler) {
            static::$handler = function ($severity, $message, $file, $line): void {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            };
        }

        return set_error_handler(static::$handler, E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    }

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
