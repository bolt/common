<?php

namespace Bolt\Common;

/**
 * This class provides shortcuts for triggering deprecation warnings for various things.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Deprecated
{
    /**
     * Shortcut for triggering a deprecation warning for a method.
     *
     * Example:
     *     class Foo
     *     {
     *         public function hello() {}
     *
     *         public function world()
     *         {
     *             Deprecated::method(3.3, 'hello');
     *         }
     *     }
     * Will trigger: "Foo::world() is deprecated since 3.3 and will be removed in 4.0. Use hello() instead."
     *
     * @param float|null $since   The version it was deprecated in
     * @param string     $suggest A method or class or suggestion of what to use instead.
     *                            If it is a class and the class has a matching method name,
     *                            that will be the suggestion.
     * @param string|int $method  The method name or the index of the call stack to reference.
     */
    public static function method($since = null, $suggest = '', $method = 0)
    {
        $function = $method;
        $constructor = false;
        if ($method === null || is_int($method)) {
            $frame = $method ?: 0;
            Assert::greaterThanEq($frame, 0);

            ++$frame; // account for this method

            $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $frame + 1);
            if (!isset($stack[$frame])) {
                throw new \OutOfBoundsException(sprintf('%s is greater than the current call stack', $frame - 1));
            }
            $caller = $stack[$frame];

            if (isset($caller['class'])) {
                $function = $caller['function'];
                if ($function === '__construct') {
                    $method = $caller['class'];
                    $constructor = true;
                } else {
                    if ($function[0] === '_' && in_array($function, ['__call', '__callStatic', '__set', '__get', '__isset', '__unset'], true)) {
                        $caller = debug_backtrace(false, $frame + 1)[$frame]; // with args
                        $caller['function'] = $caller['args'][0];
                    }
                    $method = $caller['class'] . '::' . $caller['function'];
                }
            } else {
                $method = $caller['function'];
            }
        } else {
            Assert::stringNotEmpty($method, 'Expected a non-empty string. Got: %s');
        }

        // Shortcut for suggested method
        if ($suggest && preg_match('/\s/', $suggest) === 0) {
            // Append () if it is a method/function (not a class)
            if (!class_exists($suggest)) {
                $suggest .= '()';
            } elseif (!$constructor && method_exists($suggest, $function)) {
                $suggest = $suggest . '::' . $function . '()';
            }
            $suggest = "Use $suggest instead.";
        }

        if ($constructor) {
            static::cls($method, $since, $suggest);

            return;
        }

        if ($function[0] === '_') {
            if ($function === '__isset' || $function === '__unset') {
                static::warn(substr($function, 2) . "($method)", $since, $suggest);

                return;
            }
            if ($function === '__set' || $function === '__get') {
                static::warn(strtoupper($function[2]) . "etting $method", $since, $suggest);

                return;
            }
        }

        static::warn($method . '()', $since, $suggest);
    }

    /**
     * Shortcut for triggering a deprecation warning for a class.
     *
     * @param string     $class   The class that is deprecated
     * @param float|null $since   The version it was deprecated in
     * @param string     $suggest A class or suggestion of what to use instead
     */
    public static function cls($class, $since = null, $suggest = null)
    {
        if ($suggest && preg_match('/\s/', $suggest) === 0) {
            $suggest = ltrim($suggest, '\\');
            $suggest = "Use $suggest instead.";
        }
        $class = ltrim($class, '\\');

        static::warn($class, $since, $suggest);
    }

    /**
     * Shortcut for triggering a deprecation warning for a subject.
     *
     * Examples:
     *     Deprecated::warn('Doing foo');
     *     // triggers warning: "Doing foo is deprecated."
     *
     *     Deprecated::warn('Doing foo', 3.3);
     *     // triggers warning: "Doing foo is deprecated since 3.3 and will be removed in 4.0."
     *
     *     Deprecated::warn('Doing foo', 3.3, 'Do bar instead');
     *     // triggers warning: "Doing foo is deprecated since 3.3 and will be removed in 4.0. Do bar instead."
     *
     * @param string     $subject The thing that is deprecated
     * @param float|null $since   The version it was deprecated in
     * @param string     $suggest A suggestion of what to do instead
     */
    public static function warn($subject, $since = null, $suggest = '')
    {
        $message = $subject . ' is deprecated';

        if ($since !== null) {
            $since = (string) $since;
            $message .= sprintf(' since %.1f and will be removed in %s.0', $since, $since[0] + 1);
        }

        $message .= '.';

        if ($suggest) {
            $message .= ' ' . $suggest;
        }

        static::raw($message);
    }

    /**
     * Trigger a deprecation warning.
     *
     * @param string $message The raw message
     */
    public static function raw($message)
    {
        @trigger_error($message, E_USER_DEPRECATED);
    }
}
