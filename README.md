# Bolt Common

This library provides utility functions to help simplify menial tasks.

The Bolt team believes the PHP error reporting system is a mistake. Many
built-in functions utilize it, leading to inconsistent results and head
scratching.

This library provides some wrappers around some of these functions. Our code
should always throw exceptions instead of triggering errors/warnings/notices
(excluding deprecation warnings).

Table of Contents:
- [Assert](#assert)
- [Deprecated](#deprecated)
- [Json](#json)
- [Serialization](#serialization)
- [Str](#str)

-----

## `Assert`

Additional assertions built on `Webmozart\Assert`


### `isArrayAccessible`

Throws `InvalidArgumentException` if `$value` is not an array or object
implementing `ArrayAccess`.

```php
isArrayAccessible($value, string $message = ''): void
```


### `isInstanceOfAny`

Throws `InvalidArgumentException` if `$value` is not an instance of one of the
given classes/interfaces.

```php
isInstanceOfAny($value, string[] $classes, string $message = ''): void
```

### `isIterable`

Throws `InvalidArgumentException` if `$value` is not an _iterable_. Same as
`isTraversable()`, just a better name.

```php
isIterable($value, string $message = ''): void
```


## `Deprecated`

Helper methods for triggering deprecation warnings.


### `warn`

Shortcut for triggering a deprecation warning for something.

```php
warn(string $subject, string|float $since = null, string $suggest = ''): void
```

Examples:

```php
// Triggers warning: "Doing foo is deprecated."
Deprecated::warn('Doing foo');

// Triggers warning: "Doing foo is deprecated since 3.3 and will be removed in 4.0."
Deprecated::warn('Doing foo', 3.3);

// Triggers warning: "Doing foo is deprecated since 3.3 and will be removed in 4.0. Do bar instead."
Deprecated::warn('Doing foo', 3.3, 'Do bar instead');
```


### `method`

Shortcut for triggering a deprecation warning for a method.

```php
method(string|float $since = null, string $suggest = '', string $method = null): void
```

`$suggest` can be a sentence describing what to use instead. Or it can be a
method name or `class::method` which will be converted to a sentence.

`$method` defaults to the method/function it was called from.
  - If called from constructor, warning message says the class is deprecated.
  - If called from magic method, warning message says the method/property
    called with is deprecated.

Example:

```php
class Foo
{
    public function world()
    {
        // Triggers warning: "Foo::world() is deprecated since 3.3 and will be removed in 4.0. Use hello() instead."
        Deprecated::method(3.3, 'hello');
    }
}
```


### `cls`

Shortcut for triggering a deprecation warning for a class.

```php
cls(string $class, string|float $since = null, string $suggest = null): void
```

`$suggest` can be a sentence describing what to use instead. Or it can be a
class name which will be converted to a sentence.

Examples:

```php
// Triggers warning: "Foo\Bar is deprecated."
Deprecated::cls('Foo\Bar');

// Triggers warning: "Foo\Bar is deprecated. Use Bar\Baz instead."
Deprecated::cls('Foo\Bar', null, 'Bar\Baz');
```


## `Json`

Handles JSON parsing/dumping with error handling.


### `parse`

Parses JSON _string_ to _array_ or _scalar_.
Throws `ParseException` if anything goes wrong.

```php
parse(string $json, int $options = 0, int $depth = 512): string
```

We use [`seld/jsonlint`](https://github.com/Seldaek/jsonlint) to determine why
the parsing failed and include it in the exception message.


### `dump`

Dumps _mixed_ to JSON _string_. Throws `DumpException` if anything goes wrong.

```php
dump(mixed $data, int $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE, int $depth = 512): string
```

If input contains invalid UTF-8 characters we try to convert these for you
before failing.


### `test`

Returns whether the string is valid JSON.

```php
test(string $json): bool
```


## `Serialization`

Handles PHP serialization parsing/dumping with error handling.


### `parse`

Parses PHP serialized _string_.

Throws `ParseException` if a serialized class cannot be found or anything else
goes wrong.

```php
parse(string $value, array $options = []): mixed
```

Note: `$options` parameter is ignored on PHP 5.

See [`unserialize()`](http://php.net/manual/en/function.unserialize.php) for
details.


### `dump`

Dumps anything to a PHP serialized _string_.

Throws `DumpException` if the input is not serializable or anything else goes
wrong.

```php
dump(mixed $value): string
```


## `Str`

Common string methods.


### `replaceFirst`

Replaces the first occurrence of the $search text on the $subject.

```php
replaceFirst(string $subject, string $search, string $replace, bool $caseSensitive = true): string
```


### `replaceLast`

Replaces the last occurrence of the $search text on the $subject.

```php
replaceLast(string $subject, string $search, string $replace, bool $caseSensitive = true): string
```


### `removeFirst`

Removes the first occurrence of the $search text on the $subject.

```php
removeFirst(string $subject, string $search, bool $caseSensitive = true): string
```


### `removeLast`

Removes the last occurrence of the $search text on the $subject.

```php
removeLast(string $subject, string $search, bool $caseSensitive = true): string
```


### `splitFirst`

Splits a $subject on the $delimiter and returns the first part.

```php
splitFirst(string $subject, string $delimiter): string
```


### `splitLast`

Splits a $subject on the $delimiter and returns the last part.

```php
splitLast(string $subject, string $delimiter): string
```

### `endsWith`

Returns whether the subjects ends with the search string.

```php
endsWith(string $subject, string $search, bool $caseSensitive = true): bool
```


### `className`

Returns the class name without the namespace, of a string FQCN, or object.

```php
className(string|object $class): string
```


### `humanize`

Converts a string from camel case and snake case to a human readable string.

```php
humanize(string $text): string
```


### `camelCase`

Converts a string from snake case to camel case.

```php
camelCase(string $text, bool $lowercaseFirstChar = false): string
```


### `snakeCase`

Converts a string from camel case to snake case.

```php
snakeCase(string $text): string
```
