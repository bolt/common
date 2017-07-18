<?php

namespace Bolt\Common\Tests;

use Bolt\Common\Deprecated;
use PHPUnit\Framework\TestCase;

/**
 * @author Carson Full <carsonfull@gmail.com>
 */
class DeprecatedTest extends TestCase
{
    protected $deprecations = [];

    public function testMethod()
    {
        Deprecated::method(3.0, 'baz', 'Foo::bar');
        $this->assertDeprecation('Foo::bar() is deprecated since 3.0 and will be removed in 4.0. Use baz() instead.');

        $realClass = static::class;
        Deprecated::method(3.0, $realClass, 'Foo::bar');
        $this->assertDeprecation("Foo::bar() is deprecated since 3.0 and will be removed in 4.0. Use $realClass instead.");

        Deprecated::method(3.0, 'Do it this way instead.', 'Foo::bar');
        $this->assertDeprecation('Foo::bar() is deprecated since 3.0 and will be removed in 4.0. Do it this way instead.');
    }

    public function testMethodUsingBacktrace()
    {
        TestDeprecatedClass::foo();
        $this->assertDeprecation('Bolt\Common\Tests\TestDeprecatedClass::foo() is deprecated.');

        deprecatedFunction();
        $this->assertDeprecation('Bolt\Common\Tests\deprecatedFunction() is deprecated.');

        TestDeprecatedClass::magicStatic();
        $this->assertDeprecation('Bolt\Common\Tests\TestDeprecatedClass::magicStatic() is deprecated.');

        $cls = new TestDeprecatedClass();
        $cls->magic();
        $this->assertDeprecation('Bolt\Common\Tests\TestDeprecatedClass::magic() is deprecated.');

        $cls->magic;
        $this->assertDeprecation('Getting Bolt\Common\Tests\TestDeprecatedClass::magic is deprecated.');

        $cls->magic = 'derp';
        $this->assertDeprecation('Setting Bolt\Common\Tests\TestDeprecatedClass::magic is deprecated.');

        isset($cls->magic);
        $this->assertDeprecation('isset(Bolt\Common\Tests\TestDeprecatedClass::magic) is deprecated.');
        unset($cls->magic);
        $this->assertDeprecation('unset(Bolt\Common\Tests\TestDeprecatedClass::magic) is deprecated.');

        new TestDeprecatedClass(true);
        $this->assertDeprecation('Bolt\Common\Tests\TestDeprecatedClass is deprecated.');
    }

    public function testClass()
    {
        Deprecated::cls('Foo\Bar');
        $this->assertDeprecation('Foo\Bar is deprecated.');
        Deprecated::cls('Foo\Bar', null, 'Bar\Baz');
        $this->assertDeprecation('Foo\Bar is deprecated. Use Bar\Baz instead.');
        Deprecated::cls('Foo\Bar', null, 'Do it this way instead.');
        $this->assertDeprecation('Foo\Bar is deprecated. Do it this way instead.');
    }

    public function testService()
    {
        Deprecated::service('foo');
        $this->assertDeprecation("Accessing container service 'foo' is deprecated.");
        Deprecated::service('foo', null, 'bar');
        $this->assertDeprecation("Accessing container service 'foo' is deprecated. Use 'bar' service instead.");
        Deprecated::service('foo', null, 'Do it this way instead.');
        $this->assertDeprecation("Accessing container service 'foo' is deprecated. Do it this way instead.");
    }

    public function testWarn()
    {
        Deprecated::warn('Foo bar');
        $this->assertDeprecation('Foo bar is deprecated.');

        Deprecated::warn('Foo bar', 3.0);
        $this->assertDeprecation('Foo bar is deprecated since 3.0 and will be removed in 4.0.');
        Deprecated::warn('Foo bar', 3.3);
        $this->assertDeprecation('Foo bar is deprecated since 3.3 and will be removed in 4.0.');

        Deprecated::warn('Foo bar', null, 'Use baz instead.');
        $this->assertDeprecation('Foo bar is deprecated. Use baz instead.');
        Deprecated::warn('Foo bar', 3.0, 'Use baz instead.');
        $this->assertDeprecation('Foo bar is deprecated since 3.0 and will be removed in 4.0. Use baz instead.');
    }

    public function testRaw()
    {
        Deprecated::raw('Hello world.');
        $this->assertDeprecation('Hello world.');
    }

    protected function setUp()
    {
        $this->deprecations = [];
        set_error_handler(
            function ($type, $msg, $file, $line) {
                $this->deprecations[] = $msg;
            },
            E_USER_DEPRECATED
        );
    }

    protected function tearDown()
    {
        restore_error_handler();
    }

    private function assertDeprecation($msg)
    {
        $this->assertNotEmpty($this->deprecations, 'No deprecations triggered.');
        $this->assertEquals($msg, $this->deprecations[0]);
        $this->deprecations = [];
    }
}

class TestDeprecatedClass
{
    public function __construct($deprecatedClass = false)
    {
        if ($deprecatedClass) {
            Deprecated::method();
        }
    }

    public static function foo()
    {
        Deprecated::method();
    }

    public function __call($name, $arguments)
    {
        Deprecated::method();
    }

    public static function __callStatic($name, $arguments)
    {
        Deprecated::method();
    }

    public function __get($name)
    {
        Deprecated::method();
    }

    public function __set($name, $value)
    {
        Deprecated::method();
    }

    public function __isset($name)
    {
        Deprecated::method();
    }

    public function __unset($name)
    {
        Deprecated::method();
    }
}

function deprecatedFunction()
{
    Deprecated::method();
}
