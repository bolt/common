# Bolt Common

This library provides utility functions to help simplify menial tasks.

## `Assert` — Additonal assertions built on `Webmozart\Assert`

### `isArrayAccessible($value, $message = '')`

```php
// This will allow continued execution
Assert::isArrayAccessible([1, 2, 3]);

// This will allow continued execution
Assert::isArrayAccessible(new \ArrayObject([1, 2, 3]));

// This will throw an InvalidArgumentException
Assert::isArrayAccessible(new \stdClass());

// This will throw an InvalidArgumentException
Assert::isArrayAccessible('foo bar');
```


### `isInstanceOfAny($value, array $classes, $message = '')`

```php
// This will allow continued execution
Assert::isInstanceOfAny(new \ArrayIterator(), [\Iterator::class, \ArrayAccess::class]);

// This will allow continued execution
Assert::isInstanceOfAny(new \Exception(), [\Exception::class, \Countable::class]);

// This will throw an InvalidArgumentException
Assert::isInstanceOfAny(new \Exception(), [\ArrayAccess::class, \Countable::class]);

// This will throw an InvalidArgumentException
Assert::isInstanceOfAny([], [\stdClass::class]);
```


### `isIterable($value, $message = '')`

```php
// This will allow continued execution
Assert::isIterable([1, 2, 3]);

// This will allow continued execution
Assert::isIterable(new \ArrayObject([1, 2, 3]));

// This will throw an InvalidArgumentException
Assert::isIterable(123);

// This will throw an InvalidArgumentException
Assert::isIterable(new \stdClass());
```


## `Deprecated` — Deprecated code use notifications

### `method($since = null, $suggest = '', $method = null)`

Shortcut for triggering a deprecation warning for a method.

```php
use Bolt\Common\Deprecated;

class Foo
{
    public function hello() {}

    public function world()
    {
        // Triggers warning: "Foo::world() is deprecated since 3.3 and will be removed in 4.0. Use hello() instead."
        Deprecated::method(3.3, 'hello');
    }
}
```


### `cls($class, $since = null, $suggest = null)`

Shortcut for triggering a deprecation warning for a class.

```php
use Bolt\Common\Deprecated;

// Triggers warning: "Foo\Bar is deprecated."
Deprecated::cls('Foo\Bar');

// Triggers warning: "Foo\Bar is deprecated. Use Bar\Baz instead."
Deprecated::cls('Foo\Bar', null, 'Bar\Baz');
```


### `warn($subject, $since = null, $suggest = '')`

Shortcut for triggering a deprecation warning for a subject.

```php
use Bolt\Common\Deprecated;

// Triggers warning: "Doing foo is deprecated."
Deprecated::warn('Doing foo');

// Triggers warning: "Doing foo is deprecated since 3.3 and will be removed in 4.0."
Deprecated::warn('Doing foo', 3.3);


// Triggers warning: "Doing foo is deprecated since 3.3 and will be removed in 4.0. Do bar instead."
Deprecated::warn('Doing foo', 3.3, 'Do bar instead');
```


### `raw($message)`

Trigger a generic deprecation warning.

```php
use Bolt\Common\Deprecated;

```


## `Json` — Class to parse and dump JSON with error handling

### `parse($json, $options = 0, $depth = 512)`

```php
use Bolt\Common\Json;

// $result will be a PHP array: ['foo' => 'bar']
$result = Json::parse('{"foo": "bar"}';
```


### `dump($data, $options = 448, $depth = 512)`

```php
use Bolt\Common\Json;

$data = ['name' => 'composer/composer'];

// $result will be a JSON string equivalent to: '{"name": "composer/composer"}'
$result = Json::dump($data);
```


## `Str` — Common string methods


### `replaceFirst($subject, $search, $replace, $caseSensitive = true)`

Replaces the first occurrence of the $search text on the $subject.

```php
use Bolt\Common\Str;

// $result will be 'HelloFooHelloGoodbye'
$result = Str::replaceFirst('HelloGoodbyeHelloGoodbye', 'Goodbye', 'Foo');

// $result will be 'HelloFooHelloGoodbye'
$result = Str::replaceFirst('HelloGOODBYEHelloGoodbye', 'Goodbye', 'Foo', false);
```


### `replaceLast($subject, $search, $replace, $caseSensitive = true)`

Replaces the last occurrence of the $search text on the $subject.

```php
use Bolt\Common\Str;

// $result will be 'HelloGoodbyeFooGoodbye'
$result = Str::replaceLast('HelloGoodbyeHelloGoodbye', 'Hello', 'Foo');

// $result will be 'HelloGoodbyeFooGoodbye'
$result = Str::replaceLast('HelloGoodbyeHELLOGoodbye', 'Hello', 'Foo', false);
```


### `removeFirst($subject, $search, $caseSensitive = true)`

Removes the first occurrence of the $search text on the $subject.

```php
use Bolt\Common\Str;

// $result will be 'HelloHelloGoodbye'
$result = Str::removeFirst('HelloGoodbyeHelloGoodbye', 'Goodbye');

// $result will be 'HelloHelloGoodbye'
$result = Str::removeFirst('HelloGOODBYEHelloGoodbye', 'Goodbye', false);

```

### `removeLast($subject, $search, $caseSensitive = true)`

Removes the last occurrence of the $search text on the $subject.

```php
use Bolt\Common\Str;

// $result will be 'HelloGoodbyeGoodbye'
$result = Str::removeLast('HelloGoodbyeHelloGoodbye', 'Hello');

// $result will be 'HelloGoodbyeGoodbye'
$result = Str::removeLast('HelloGoodbyeHELLOGoodbye', 'Hello', false);
```


### `splitFirst($subject, $delimiter)`

Splits a $subject on the $delimiter and returns the first part.
  - If delimiter is empty an InvalidArgumentException is thrown
  - If the delimiter is not found in the string the string is returned

```php
use Bolt\Common\Str;

// $result will be 'herp'
$result = Str::splitFirst('herp derp terp lerp', ' ');

// $result will be 'herp derp'
$result = Str::splitFirst('herp derp', ',');
```


### `splitLast($subject, $delimiter)`

Splits a $subject on the $delimiter and returns the last part.
  - If delimiter is empty an InvalidArgumentException is thrown
  - If the delimiter is not found in the string the string is returned

```php
use Bolt\Common\Str;

// $result will be 'lerp'
$result = Str::splitLast('herp derp terp lerp', ' ');

// $result will be 'herp derp'
$result = Str::splitLast('herp derp', ',');
```


### `endsWith($subject, $search, $caseSensitive = true)`

Returns whether the subjects ends with the search string.

```php
use Bolt\Common\Str;

// $result will be true
$result = Str::endsWith('FooBar', 'Bar');

// $result will be true
$result = Str::endsWith('FooBar', 'bar', false);

// $result will be false
$result = Str::endsWith('FooBar', 'Foo');
```


### `className($class)`

Returns the class name without the namespace, of a string FQCN, or object.

```php
use Bolt\Common\Str;

// $result will be 'JavaScript'
$result = Str::className(new \Bolt\Asset\File\JavaScript());

// $result will be 'Stylesheet'
$result = Str::className('Bolt\Asset\File\Stylesheet');
```


### `humanize($text)`

Makes a technical name human readable.
  - Sequences of snake cased or camel cased are replaced by single spaces
  - The first letter of the resulting string is capitalized, while all other
    letters are turned to lowercase

```php
use Bolt\Common\Str;

// $result will be 'Foo bar'
$result = Str::humanize('fooBar');

// $result will be 'Foo bar'
$result = Str::humanize('FooBar');

// $result will be 'Foo bar'
$result = Str::humanize('foo_bar');
```


### `camelCase($text, $lowercaseFirstChar = false)`

Converts a string from snake case to camel case.

```php
use Bolt\Common\Str;

// $result will be 'FooBar'
$result = Str::camelCase('fooBar');

// $result will be 'FooBar'
$result = Str::camelCase('FooBar');

// $result will be 'FooBar'
$result = Str::camelCase('foo_bar');

// $result will be 'fooBar'
$result = Str::camelCase('foo_bar', true);
```


### `snakeCase($text)`

Converts a string from camel case to snake case.

```php
use Bolt\Common\Str;

// $result will be 'foo_bar'
$result = Str::snakeCase('fooBar');

// $result will be 'foo_bar'
$result = Str::snakeCase('FooBar');

// $result will be 'foo_bar'
$result = Str::snakeCase('foo_bar');
```
