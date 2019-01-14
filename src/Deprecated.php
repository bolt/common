<?php

declare(strict_types=1);

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
     * @param string     $suggest a method or class or suggestion of what to use instead.
     *                            If it is a class and the class has a matching method name,
     *                            that will be the suggestion
     * @param string|int $subject The method or class name or the index of the call stack to reference
     */
    public static function method($since = null, $suggest = '', $subject = 0): void
    {
        if ($subject === null || \is_int($subject)) {
            [0 => $subject, 1 => $function, 3 => $constructor] = static::getCaller($subject ?: 0);
        } else {
            Assert::stringNotEmpty($subject, 'Expected a non-empty string. Got: %s');
            $function = $subject;
            $constructor = false;
        }

        // Shortcut for suggested method
        if ($suggest && preg_match('/\s/', $suggest) === 0) {
            // Append () if it is a method/function (not a class)
            if (! class_exists($suggest)) {
                $suggest .= '()';
            } elseif (! $constructor && method_exists($suggest, $function)) {
                // $suggest is class that has matching method name and is not the constructor
                $suggest .= '::' . $function . '()';
            }
            $suggest = "Use ${suggest} instead.";
        }

        if (! $constructor) {
            $subject .= '()';
        }

        static::warn($subject, $since, $suggest);
    }

    /**
     * Get info about caller at index.
     *
     * @param int $index
     * @param int $offset
     *
     * @return array [string repr, function name, class name or false, isConstructor]
     */
    protected static function getCaller($index, $offset = 1): array
    {
        Assert::greaterThanEq($index, 0);

        $index += $offset + 1;

        $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $index + 1);
        if (! isset($stack[$index])) {
            throw new \OutOfBoundsException(sprintf('%s is greater than the current call stack', $index - $offset - 1));
        }
        $frame = $stack[$index];

        if (! isset($frame['class'])) {
            // Assert the function isn't called directly from a script,
            // else we would be saying "require() is deprecated" lol.
            if (! \function_exists($frame['function'])) {
                $frame = $stack[$index - $offset];
                throw new \InvalidArgumentException(
                    sprintf('%s::%s() must be called from within a function/method.', $frame['class'], $frame['function'])
                );
            }

            return [
                $frame['function'],
                $frame['function'],
                false,
                false,
            ];
        }

        $class = $frame['class'];
        $function = $frame['function'];
        $constructor = $function === '__construct';

        if ($function === '__call' || $function === '__callStatic') {
            $frame = debug_backtrace(0, $index + 1)[$index]; // with args
            $function = $frame['args'][0];
        }

        return [
            $class . (! $constructor ? '::' . $function : ''),
            $function,
            $class,
            $constructor,
        ];
    }

    /**
     * Shortcut for triggering a deprecation warning for a class.
     *
     * @param string     $class   The class that is deprecated
     * @param float|null $since   The version it was deprecated in
     * @param string     $suggest A class or suggestion of what to use instead
     */
    public static function cls($class, $since = null, $suggest = null): void
    {
        if ($suggest && preg_match('/\s/', $suggest) === 0) {
            $suggest = ltrim($suggest, '\\');
            $suggest = "Use ${suggest} instead.";
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
    public static function warn($subject, $since = null, $suggest = ''): void
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
    public static function raw($message): void
    {
        @trigger_error($message, E_USER_DEPRECATED);
    }
}
