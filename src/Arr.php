<?php

declare(strict_types=1);

namespace Bolt\Common;

use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;
use Traversable;

/**
 * Array functions. All of these methods accept `Traversable` or `ArrayAccess` objects in addition to arrays.
 *
 * Most of these methods are also provided in {@see Bag}, but their implementation is significant enough
 * that the backing logic lives here so they can be used standalone from Bags.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Arr
{
    /**
     * @var \stdClass|null Used with {@see remove} to unset value
     */
    private static $unsetMarker;

    /**
     * Converts an `iterable`, `null`, or `stdClass` to an array.
     *
     * @param iterable|\stdClass|null $iterable
     */
    public static function from($iterable): array
    {
        if (is_array($iterable)) {
            return $iterable;
        }
        if ($iterable instanceof Traversable) {
            return iterator_to_array($iterable);
        }
        if ($iterable === null) {
            return [];
        }
        if ($iterable instanceof \stdClass) {
            return (array) $iterable;
        }

        Assert::nullOrIsIterable($iterable);
    }

    /**
     * Recursively converts an `iterable` to nested arrays.
     *
     * @param iterable|\stdClass|null $iterable
     */
    public static function fromRecursive($iterable): array
    {
        $arr = static::from($iterable);

        foreach ($arr as $key => $value) {
            if ($value instanceof \stdClass || is_iterable($value)) {
                $value = static::fromRecursive($value);
            }
            $arr[$key] = $value;
        }

        return $arr;
    }

    /**
     * Return the values from a single column in the `$input` array, identified by the `$columnKey`.
     *
     * Optionally, an `$indexKey` may be provided to index the values in the returned array by the
     * values from the `$indexKey` column in the input array.
     *
     * Example:
     *
     *     $data = [
     *         ['id' => 10, 'name' => 'Alice'],
     *         ['id' => 20, 'name' => 'Bob'],
     *         ['id' => 30, 'name' => 'Carson'],
     *     ];
     *
     *     Arr::column($data, 'name');
     *     // => ['Alice', 'Bob', 'Carson']
     *
     *     Arr::column($data, 'name', 'id');
     *     // => [10 => 'Alice', 20 => 'Bob', 30 => 'Carson']
     *
     * <br>
     * Note: This matches the {@see array_column} function, however; it accepts an iterable not just an array,
     * it allows for mapping a list of objects implementing `ArrayAccess`, and allows for mapping a list of
     * object properties (which was added to the builtin function in PHP 7.0).
     *
     * @param iterable        $input     A list of arrays or objects from which to pull a column of values
     * @param string|int|null $columnKey The key of the values to return or `null` for no change
     * @param string|int|null $indexKey  The key of the keys to return or `null` for no change
     */
    public static function column($input, $columnKey, $indexKey = null): array
    {
        Assert::isIterable($input);

        $output = [];

        foreach ($input as $row) {
            $key = $value = null;
            $keySet = false;

            if ($columnKey === null) {
                $value = $row;
            } elseif (is_array($row) && array_key_exists($columnKey, $row)) {
                $value = $row[$columnKey];
            } elseif ($row instanceof ArrayAccess && isset($row[$columnKey])) {
                $value = $row[$columnKey];
            } elseif (is_object($row) && isset($row->{$columnKey})) {
                $value = $row->{$columnKey};
            } else {
                continue;
            }

            if ($indexKey !== null) {
                /*
                 * For arrays, we use array_key_exists because isset returns false for keys that exist with null values.
                 * For ArrayAccess we assume devs are smarter and don't have this edge case. Regardless, we don't have
                 * another way to check so it's up to them.
                 */
                if (is_array($row) && array_key_exists($indexKey, $row)) {
                    $keySet = true;
                    $key = (string) $row[$indexKey];
                } elseif ($row instanceof ArrayAccess && isset($row[$indexKey])) {
                    $keySet = true;
                    $key = (string) $row[$indexKey];
                } elseif (is_object($row) && isset($row->{$indexKey})) {
                    $keySet = true;
                    $key = (string) $row->{$indexKey};
                }
            }

            if ($keySet) {
                $output[$key] = $value;
            } else {
                $output[] = $value;
            }
        }

        return $output;
    }

    /**
     * Returns whether a key exists from an array or `ArrayAccess` object using path syntax to check nested data.
     *
     * This method does not allow for keys that contain `/`.
     *
     * Example:
     *
     *     // Check if the the bar key of a set of nested arrays exists.
     *     // This is equivalent to isset($data['foo']['baz']['bar']) but won't
     *     // throw warnings for missing keys.
     *     Arr::has($data, 'foo/baz/bar');
     *
     * <br>
     * Note: Using `isset()` with nested data, like `isset($data['a']['b'])`, won't call `offsetExists` for 'a'.
     * It calls `offsetGet('a')` and if 'a' doesn't exist and an `isset` check isn't done in `offsetGet`, a warning is
     * triggered. It could be argued that that `ArrayAccess` object should fix this in their implementation of
     * `offsetGet`, and I would agree. Regardless I think this is nicer syntax.
     *
     * @param array|ArrayAccess $data Data to check values from
     * @param string            $path Path to traverse and check keys from
     */
    public static function has($data, string $path): bool
    {
        Assert::isArrayAccessible($data);
        Assert::stringNotEmpty($path);

        $path = explode('/', $path);
        $part = array_shift($path);

        while ($part !== null) {
            if (! ($data instanceof ArrayAccess) && ! is_array($data)) {
                return false;
            }
            if (! (isset($data[$part]) || array_key_exists($part, $data))) {
                return false;
            }
            $data = $data[$part];

            $part = array_shift($path);
        }

        return true;
    }

    /**
     * Gets a value from an array or `ArrayAccess` object using path syntax to retrieve nested data.
     *
     * Example:
     *
     *     // Get the bar key of a set of nested arrays.
     *     // This is equivalent to $data['foo']['baz']['bar'] but won't
     *     // throw warnings for missing keys.
     *     Arr::get($data, 'foo/baz/bar');
     *
     * This method does not allow for keys that contain `/`.
     *
     * This code is adapted from Michael Dowling in his Guzzle library.
     *
     * @param array|ArrayAccess $data    Data to retrieve values from
     * @param string            $path    Path to traverse and retrieve a value from
     * @param mixed|null        $default Default value to return if key does not exist
     *
     * @return mixed|null
     */
    public static function get($data, string $path, $default = null)
    {
        Assert::isArrayAccessible($data);
        Assert::stringNotEmpty($path);

        $path = explode('/', $path);
        $part = array_shift($path);

        while ($part !== null) {
            if ((! is_array($data) && ! ($data instanceof ArrayAccess)) || ! isset($data[$part])) {
                return $default;
            }
            $data = $data[$part];

            $part = array_shift($path);
        }

        return $data;
    }

    /**
     * Sets a value in a nested array or `ArrayAccess` object using path syntax to set nested data.
     * Inner arrays will be created as needed to set the value.
     *
     * Example:
     *
     *     // Set an item at a nested structure
     *     Arr::set($data, 'nested/path/hello', 'world');
     *
     *     // Append to a list in a nested structure
     *     Arr::get($data, 'foo/baz');
     *     // => null
     *     Arr::set($data, 'foo/baz/[]', 'a');
     *     Arr::set($data, 'foo/baz/[]', 'b');
     *     Arr::get($data, 'foo/baz');
     *     // => ['a', 'b']
     *
     * This function does not support keys that contain `/` or `[]` characters
     * because these are special tokens used when traversing the data structure.
     * A value may be appended to an existing array by using `[]` as the final
     * key of a path.
     *
     * <br>
     * Note: To set values in arrays that are in `ArrayAccess` objects their
     * `offsetGet()` method needs to be able to return arrays by reference.
     *
     * This code is adapted from Michael Dowling in his Guzzle library.
     *
     * @param array|ArrayAccess $data  Data to modify by reference
     * @param string            $path  Path to set
     * @param mixed             $value Value to set at the key
     *
     * @throws \ErrorException
     */
    public static function set(&$data, string $path, $value): void
    {
        Assert::isArrayAccessible($data);
        Assert::stringNotEmpty($path);

        $queue = explode('/', $path);
        // Optimization for simple sets.
        if (\count($queue) === 1) {
            if ($path === '[]') {
                $data[] = $value;
            } elseif (static::$unsetMarker && $value === static::$unsetMarker) {
                unset($data[$path]);
            } else {
                $data[$path] = $value;
            }

            return;
        }

        $invalidKey = null;
        $current = &$data;
        $key = array_shift($queue);

        while ($key !== null) {
            if (! is_array($current) && ! ($current instanceof ArrayAccess)) {
                throw new RuntimeException(
                    sprintf(
                        "Cannot set '%s', because '%s' is already set and not an array or an object implementing ArrayAccess.",
                        $path,
                        $invalidKey
                    )
                );
            }
            if (! $queue) {
                if ($key === '[]') {
                    $current[] = $value;
                } elseif (static::$unsetMarker && $value === static::$unsetMarker) {
                    unset($current[$key]);
                } else {
                    $current[$key] = $value;
                }

                return;
            }

            if (! isset($current[$key])) {
                $current[$key] = [];
            }

            $next = null;
            if ($current instanceof ArrayAccess && ! static::canReturnArraysByReference($current, $key, $next, $e)) {
                throw new RuntimeException(
                    sprintf(
                        "Cannot set '%s', because '%s' is an %s which does not return arrays by reference from its offsetGet() method.",
                        $path,
                        $invalidKey,
                        \get_class($current)
                    ),
                    0,
                    $e
                );
            }

            // If checking if object can return arrays by ref needed to fetch the value in the object then
            // use that so we don't have to fetch the value again.
            if ($next !== null) {
                $current = &$next;
                unset($next); // so assigning null above doesn't wipe out actual data
            } else {
                $current = &$current[$key];
            }

            $invalidKey = $key;

            $key = array_shift($queue);
        }
    }

    /**
     * Removes and returns a value from an array or `ArrayAccess` object using path syntax to remove nested data.
     *
     * Example:
     *
     *     Arr::remove($data, 'foo/bar');
     *     // => 'baz'
     *     Arr::remove($data, 'foo/bar');
     *     // => null
     *
     * This function does not support keys that contain `/`.
     *
     * <br>
     * Note: To remove values in arrays that are in `ArrayAccess` objects their
     * `offsetGet()` method needs to be able to return arrays by reference.
     *
     * @param array|ArrayAccess $data    Data to retrieve remove value from
     * @param string            $path    Path to traverse
     * @param mixed|null        $default Default value to return if key does not exist
     *
     * @throws \ErrorException
     */
    public static function remove(&$data, string $path, $default = null)
    {
        if (! static::$unsetMarker) {
            static::$unsetMarker = new \stdClass();
        }

        // Call get() with special default value so we can know if the key exists without calling has()
        $value = static::get($data, $path, static::$unsetMarker);

        /*
         * If the path doesn't exist don't call set().
         * This also prevents set() from creating middle arrays to get to the leaf node,
         * which doesn't make sense in this case since we are just trying to remove the leaf node.
         */
        if ($value === static::$unsetMarker) {
            return $default;
        }

        // Set with special marker to unset value at path
        static::set($data, $path, static::$unsetMarker);

        return $value;
    }

    /**
     * Returns whether the value is an array or an object implementing `ArrayAccess`.
     */
    public static function isAccessible($value): bool
    {
        return $value instanceof ArrayAccess || is_array($value);
    }

    /**
     * Asserts that the given value is an array or an object implementing `ArrayAccess`.
     *
     * @throws InvalidArgumentException when it is not
     *
     * @deprecated since 1.0 and will be removed in 2.0. Use {@see \Bolt\Common\Assert::isArrayAccessible} instead.
     */
    public static function assertAccessible($value): void
    {
        Deprecated::method(1.0, 'Bolt\Common\Assert::isArrayAccessible');

        Assert::isArrayAccessible($value);
    }

    /**
     * Returns whether the `$iterable` is an associative mapping.
     *
     * Note: Empty arrays are not.
     *
     * @param iterable $iterable
     */
    public static function isAssociative($iterable): bool
    {
        if ($iterable instanceof Traversable) {
            $iterable = iterator_to_array($iterable);
        }
        if (! is_array($iterable) || $iterable === []) {
            return false;
        }

        return array_keys($iterable) !== range(0, \count($iterable) - 1);
    }

    /**
     * Returns whether the `$iterable` is an indexed list - zero indexed and sequential.
     *
     * Note: Empty iterables are.
     *
     * @param iterable $iterable
     */
    public static function isIndexed($iterable): bool
    {
        if (! is_iterable($iterable)) {
            return false;
        }

        return ! static::isAssociative($iterable);
    }

    /**
     * Returns an array with the `$callable` applied to each leaf value in the `$iterable`.
     *
     * This converts all `Traversable` objects to arrays.
     *
     * @param iterable $iterable
     * @param callable $callable Function is passed `($value, $key)`
     *
     * @throws \ReflectionException
     */
    public static function mapRecursive($iterable, callable $callable): array
    {
        Assert::isIterable($iterable);

        // If internal method with one arg, like strtolower, limit to first arg so warning isn't triggered.
        $ref = new \ReflectionFunction($callable);
        if ($ref->isInternal() && $ref->getNumberOfParameters() === 1) {
            $callable = function ($arg) use ($callable) {
                return $callable($arg);
            };
        }

        return static::doMapRecursive($iterable, $callable);
    }

    /**
     * Internal method do actual recursion after args have been validated by main method.
     *
     * @param iterable $iterable
     */
    private static function doMapRecursive($iterable, callable $callable): array
    {
        $mapped = [];
        foreach ($iterable as $key => $value) {
            $mapped[$key] = is_iterable($value) ?
                static::doMapRecursive($value, $callable) :
                $callable($value, $key);
        }

        return $mapped;
    }

    /**
     * Replaces values from second iterable into first iterable recursively.
     *
     * This differs from {@see array_replace_recursive} in a couple ways:
     *
     *  - Lists (indexed arrays) from second array completely replace list in first array.
     *
     *  - Null values from second array do not replace lists or associative arrays in first
     *    (they do still replace scalar values).
     *
     * This converts all `Traversable` objects to arrays.
     *
     * @param iterable $iterable1
     * @param iterable $iterable2
     *
     * @return array The combined array
     */
    public static function replaceRecursive($iterable1, $iterable2): array
    {
        Assert::allIsIterable([$iterable1, $iterable2]);

        if ($iterable1 instanceof Traversable) {
            $iterable1 = iterator_to_array($iterable1);
        }
        if ($iterable2 instanceof Traversable) {
            $iterable2 = iterator_to_array($iterable2);
        }

        $merged = $iterable1;

        foreach ($iterable2 as $key => $value) {
            if ($value instanceof Traversable) {
                $value = iterator_to_array($value);
            }
            if (is_array($value) && static::isAssociative($value)
                && isset($merged[$key]) && is_iterable($merged[$key])
            ) {
                $merged[$key] = static::replaceRecursive($merged[$key], $value);
            } elseif ($value === null && isset($merged[$key]) && is_iterable($merged[$key])) {
                // Convert iterable to array to be consistent.
                if ($merged[$key] instanceof Traversable) {
                    $merged[$key] = iterator_to_array($merged[$key]);
                }
                continue;
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Determine whether the ArrayAccess object can return by reference.
     *
     * @param string           $key   The key to try with
     * @param ArrayAccess|null $value The value if it needed to be fetched
     * @param \ErrorException  $ex
     *
     * @throws \ErrorException
     * @throws \ReflectionException
     */
    private static function canReturnArraysByReference(ArrayAccess $obj, $key, &$value, &$ex): bool
    {
        static $supportedClasses = [
            // These fail reflection check below even though they work fine :rolleyes:
            \ArrayObject::class => true,
            \ArrayIterator::class => true,
            \RecursiveArrayIterator::class => true,
        ];
        static $noErrors = [];

        $class = \get_class($obj);

        /*
         * Check to see if offsetGet() is defined to return reference (with "&" before method name).
         * This prevents us from triggering indirect modification notices.
         * We know for sure that the method cannot return by reference if not defined correctly, so we cache false.
         * We do not know for sure that the method can return by reference if it is defined correctly, so we cache
         * null instead of true. This allows the reflection check to only happen once, but still drop through to
         * validation below.
         */
        if (! isset($supportedClasses[$class])) {
            $supportedClasses[$class] = (new \ReflectionMethod($obj, 'offsetGet'))->returnsReference() ? null : false;
        }

        // If definite value return that, else run validation below.
        if ($supportedClasses[$class] !== null) {
            return $supportedClasses[$class];
        }

        if (isset($noErrors[$class])) {
            $value1 = &$obj[$key];
        } else {
            Thrower::set();
            try {
                $value1 = &$obj[$key];
            } catch (\ErrorException $e) {
                $msg = $e->getMessage();
                if ($msg === 'Only variable references should be returned by reference' ||
                    mb_strpos($msg, 'Indirect modification of overloaded element') === 0
                ) {
                    $ex = $e;

                    return $supportedClasses[$class] = false;
                }
                throw $e;
            } finally {
                restore_error_handler();
            }

            // We assume the object is not going to trigger warnings at this point
            $noErrors[$class] = true;
        }

        // We cannot validate this result because objects are always returned by reference (and scalars do not matter).
        if (! is_array($value1)) {
            // return value (via parameter) so set() doesn't have to fetch the item again.
            // We cannot do this if is an array because it will be the value instead of the reference.
            $value = $value1;

            return true;
        }

        // Verify the object can return arrays by reference.
        $value2 = &$obj[$key];
        $testKey = uniqid('__reference_test_');
        $value1[$testKey] = 'test';
        $supportedClasses[$class] = isset($value2[$testKey]);
        unset($value1[$testKey]);

        return $supportedClasses[$class];
    }

    /**
     * Flattens an iterable.
     *
     * Example:
     *
     *     // Flatten one level
     *     Arr::flatten([[1, 2], [[3]], 4])
     *     // => [1, 2, [3], 4]
     *
     *     // Flatten all levels
     *     Arr::flatten([[1, 2], [[3]], 4], INF)
     *     // => [1, 2, 3, 4]
     *
     * @param iterable $iterable The iterable to flatten
     * @param int      $depth    How deep to flatten
     */
    public static function flatten($iterable, $depth = 1): array
    {
        Assert::isIterable($iterable);

        return static::doFlatten(
            $iterable,
            $depth,
            'is_iterable' // This may be more configurable in the future.
        );
    }

    /**
     * Internal method to do actual flatten recursion after args have been validated by main method.
     *
     * @param iterable $iterable  The iterable to flatten
     * @param int      $depth     How deep to flatten
     * @param callable $predicate Whether to recurse the item
     */
    private static function doFlatten($iterable, $depth, callable $predicate, array $result = []): array
    {
        foreach ($iterable as $item) {
            if ($depth >= 1 && $predicate($item)) {
                $result = static::doFlatten($item, $depth - 1, $predicate, $result);
            } else {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Private Constructor.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
