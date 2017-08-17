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
    }

    public function testMethodSentenceSuggestion()
    {
        Deprecated::method(null, 'Do it this way instead.', 'Foo::bar');
        $this->assertDeprecation('Foo::bar() is deprecated. Do it this way instead.');
    }

    public function testMethodSuggestClass()
    {
        TestDeprecatedClass::foo();
        $this->assertDeprecation(TestDeprecatedClass::class . '::foo() is deprecated. Use ArrayObject instead.');
    }

    public function testMethodSuggestClassWithMatchingMethod()
    {
        TestDeprecatedClass::getArrayCopy();
        $this->assertDeprecation(TestDeprecatedClass::class . '::getArrayCopy() is deprecated. Use ArrayObject::getArrayCopy() instead.');
    }

    public function testMethodConstructor()
    {
        new TestDeprecatedClass(true);
        $this->assertDeprecation(TestDeprecatedClass::class . ' is deprecated. Use ArrayObject instead.');
    }

    public function testMethodMagicCall()
    {
        /* @noinspection PhpUndefinedMethodInspection */
        TestDeprecatedClass::magicStatic();
        $this->assertDeprecation(TestDeprecatedClass::class . '::magicStatic() is deprecated. Use ArrayObject instead.');

        /* @noinspection PhpUndefinedMethodInspection */
        TestDeprecatedClass::append();
        $this->assertDeprecation(TestDeprecatedClass::class . '::append() is deprecated. Use ArrayObject::append() instead.');

        $cls = new TestDeprecatedClass();
        /* @noinspection PhpUndefinedMethodInspection */
        $cls->magic();
        $this->assertDeprecation(TestDeprecatedClass::class . '::magic() is deprecated. Use ArrayObject instead.');
        /* @noinspection PhpUndefinedMethodInspection */
        $cls->append();
        $this->assertDeprecation(TestDeprecatedClass::class . '::append() is deprecated. Use ArrayObject::append() instead.');
    }

    public function testMethodFunction()
    {
        eval('namespace Bolt\Common { function deprecatedFunction() { Deprecated::method(); }; deprecatedFunction(); }');
        $this->assertDeprecation('Bolt\Common\deprecatedFunction() is deprecated.');
    }

    public function testMethodIndex()
    {
        TestDeprecatedClass::someMethod();
        $this->assertDeprecation(TestDeprecatedClass::class . '::someMethod() is deprecated. Use ArrayObject instead.');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected a value greater than or equal to 0. Got: -1
     */
    public function testMethodIndexNegative()
    {
        Deprecated::method(null, null, -1);
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage 9000 is greater than the current call stack
     */
    public function testMethodIndexOutOfBounds()
    {
        Deprecated::method(null, null, 9000);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected a non-empty string. Got: boolean
     */
    public function testMethodNotIntOrString()
    {
        Deprecated::method(null, null, false);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected a non-empty string. Got: ""
     */
    public function testMethodEmptyString()
    {
        Deprecated::method(null, null, '');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Bolt\Common\Deprecated::method() must be called from within a function/method.
     */
    public function testMethodNotFunction()
    {
        // Using eval here because it is the easiest, but this also applies to require(_once)/include(_once)
        eval('\Bolt\Common\Deprecated::method();');
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
