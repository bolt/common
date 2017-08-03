<?php

namespace Bolt\Common\Tests;

use Bolt\Common\Deprecated;
use Bolt\Common\Tests\Fixtures\TestDeprecatedClass;
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
        $this->assertDeprecation(
            "Foo::bar() is deprecated since 3.0 and will be removed in 4.0. Use $realClass instead."
        );

        Deprecated::method(3.0, 'Do it this way instead.', 'Foo::bar');
        $this->assertDeprecation(
            'Foo::bar() is deprecated since 3.0 and will be removed in 4.0. Do it this way instead.'
        );
    }

    public function testMethodUsingBacktrace()
    {
        TestDeprecatedClass::foo();
        $this->assertDeprecation('Bolt\Common\Tests\Fixtures\TestDeprecatedClass::foo() is deprecated.');

        Fixtures\deprecatedFunction();
        $this->assertDeprecation('Bolt\Common\Tests\Fixtures\deprecatedFunction() is deprecated.');

        /* @noinspection PhpUndefinedMethodInspection */
        TestDeprecatedClass::magicStatic();
        $this->assertDeprecation('Bolt\Common\Tests\Fixtures\TestDeprecatedClass::magicStatic() is deprecated.');

        $cls = new TestDeprecatedClass();
        /* @noinspection PhpUndefinedMethodInspection */
        $cls->magic();
        $this->assertDeprecation('Bolt\Common\Tests\Fixtures\TestDeprecatedClass::magic() is deprecated.');

        /* @noinspection PhpUndefinedFieldInspection */
        $cls->magic;
        $this->assertDeprecation('Getting Bolt\Common\Tests\Fixtures\TestDeprecatedClass::magic is deprecated.');

        /* @noinspection PhpUndefinedFieldInspection */
        $cls->magic = 'derp';
        $this->assertDeprecation('Setting Bolt\Common\Tests\Fixtures\TestDeprecatedClass::magic is deprecated.');

        isset($cls->magic);
        $this->assertDeprecation('isset(Bolt\Common\Tests\Fixtures\TestDeprecatedClass::magic) is deprecated.');
        unset($cls->magic);
        $this->assertDeprecation('unset(Bolt\Common\Tests\Fixtures\TestDeprecatedClass::magic) is deprecated.');

        new TestDeprecatedClass(true);
        $this->assertDeprecation('Bolt\Common\Tests\Fixtures\TestDeprecatedClass is deprecated. Use ArrayObject instead.');

        TestDeprecatedClass::getArrayCopy();
        $this->assertDeprecation('Bolt\Common\Tests\Fixtures\TestDeprecatedClass::getArrayCopy() is deprecated. Use ArrayObject::getArrayCopy() instead.');
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
