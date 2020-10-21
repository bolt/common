<?php

namespace Bolt\Common\Tests;

use Bolt\Common\Str;

class StrTest extends TestCase
{
    public function testReplaceFirst()
    {
        $this->assertSame(
            'HelloFooHelloGoodbye',
            Str::replaceFirst('HelloGoodbyeHelloGoodbye', 'Goodbye', 'Foo')
        );
        $this->assertSame(
            'HelloFooHelloGoodbye',
            Str::replaceFirst('HelloGOODBYEHelloGoodbye', 'Goodbye', 'Foo', false)
        );

        $this->assertSame(
            'HelloGoodbye',
            Str::replaceFirst('HelloGoodbye', 'red', 'blue')
        );
    }

    public function testReplaceLast()
    {
        $this->assertSame(
            'HelloGoodbyeFooGoodbye',
            Str::replaceLast('HelloGoodbyeHelloGoodbye', 'Hello', 'Foo')
        );
        $this->assertSame(
            'HelloGoodbyeFooGoodbye',
            Str::replaceLast('HelloGoodbyeHELLOGoodbye', 'Hello', 'Foo', false)
        );

        $this->assertSame(
            'HelloGoodbye',
            Str::replaceLast('HelloGoodbye', 'red', 'blue')
        );
    }

    public function testRemoveFirst()
    {
        $this->assertSame('HelloHelloGoodbye', Str::removeFirst('HelloGoodbyeHelloGoodbye', 'Goodbye'));
        $this->assertSame('HelloHelloGoodbye', Str::removeFirst('HelloGOODBYEHelloGoodbye', 'Goodbye', false));

        $this->assertSame('abc', Str::removeFirst('abc', 'zxc'));
    }

    public function testRemoveLast()
    {
        $this->assertSame('HelloGoodbyeGoodbye', Str::removeLast('HelloGoodbyeHelloGoodbye', 'Hello'));
        $this->assertSame('HelloGoodbyeGoodbye', Str::removeLast('HelloGoodbyeHELLOGoodbye', 'Hello', false));

        $this->assertSame('abc', Str::removeLast('abc', 'zxc'));
    }

    public function testSplitFirst()
    {
        $this->assertSame('herp', Str::splitFirst('herp derp foo bar', ' '));
        $this->assertSame('herp derp', Str::splitFirst('herp derp', ','));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSplitFirstEmptyDelimiter()
    {
        $this->assertFalse(Str::splitFirst('herp derp', ''));
    }

    public function testSplitLast()
    {
        $this->assertSame('bar', Str::splitLast('herp derp foo bar', ' '));
        $this->assertSame('herp derp', Str::splitLast('herp derp', ','));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSplitLastEmptyDelimiter()
    {
        $this->assertFalse(Str::splitLast('herp derp', ''));
    }

    public function testEndsWith()
    {
        $this->assertTrue(Str::endsWith('FooBar', 'Bar'));
        $this->assertTrue(Str::endsWith('FooBar', 'bar', false));
        $this->assertFalse(Str::endsWith('FooBar', 'Foo'));
    }

    public function testStartsWith()
    {
        $this->assertTrue(Str::startsWith('FooBar', 'Foo'));
        $this->assertTrue(Str::startsWith('FooBar', 'foo', false));
        $this->assertFalse(Str::startsWith('FooBar', 'Qux'));
    }

    public function testEnsureEndsWith()
    {
        $this->assertsame('FooBar_', Str::ensureEndsWith('FooBar_', '_'));
        $this->assertsame('FooBar_', Str::ensureEndsWith('FooBar', '_'));
        $this->assertSame('FooBar__', Str::ensureEndsWith('FooBar__', '__'));
        $this->assertSame('FooBar___', Str::ensureEndsWith('FooBar', '___'));
        $this->assertsame('FooBar', Str::ensureEndsWith('FooBar', 'FooBar'));
    }

    public function testEnsureStartsWith()
    {
        $this->assertsame('_FooBar', Str::ensureStartsWith('FooBar', '_'));
        $this->assertsame('_FooBar', Str::ensureStartsWith('_FooBar', '_'));
        $this->assertSame('__FooBar', Str::ensureStartsWith('__FooBar', '__'));
        $this->assertSame('___FooBar', Str::ensureStartsWith('FooBar', '___'));
        $this->assertsame('FooBar', Str::ensureStartsWith('FooBar', 'FooBar'));
    }

    public function testClassName()
    {
        $this->assertSame('StrTest', Str::className($this));
        $this->assertSame('StrTest', Str::className(static::class));
    }

    public function testCamelCase()
    {
        $this->assertSame('FooBar', Str::camelCase('fooBar'));
        $this->assertSame('FooBar', Str::camelCase('FooBar'));
        $this->assertSame('FooBar', Str::camelCase('foo_bar'));

        $this->assertSame('fooBar', Str::camelCase('foo_bar', true));
    }

    public function testHumanize()
    {
        $this->assertSame('Foo bar', Str::humanize('fooBar'));
        $this->assertSame('Foo bar', Str::humanize('FooBar'));
        $this->assertSame('Foo bar', Str::humanize('foo_bar'));
    }

    public function testSnakeCase()
    {
        $this->assertSame('foo_bar', Str::snakeCase('fooBar'));
        $this->assertSame('foo_bar', Str::snakeCase('FooBar'));
        $this->assertSame('foo_bar', Str::snakeCase('foo_bar'));
    }

    public function testMbStrReplace()
    {
        $this->assertSame('fooXXX', Str::mb_substr_replace('fooBarQuxBzzzz', 'XXX', 3));
        $this->assertSame('fooXXXrQuxBzzzz', Str::mb_substr_replace('fooBarQuxBzzzz', 'XXX', 3, 2));
        $this->assertSame('fooXXXz', Str::mb_substr_replace('fooBarQuxBzzzz', 'XXX', 3, 10));
        $this->assertSame('XXX', Str::mb_substr_replace('fooBarQuxBzzzz', 'XXX', 0));
        $this->assertSame('foXXXXX', Str::mb_substr_replace('foo', 'XXXXX', 2));
        $this->assertSame('fooXXXr😎Qux🧙‍♂️Bzzzz', Str::mb_substr_replace('fooBar😎Qux🧙‍♂️Bzzzz', 'XXX', 3, 2));
        $this->assertSame('foo💁‍♂️z', Str::mb_substr_replace('fooBarQuxBzzzz', '💁‍♂️', 3, 10));
    }

    public function testPlaceholder()
    {
        $this->assertSame(
            'You are the apple to my eye.',
            Str::placeholders('You are the {FOO} to my {BAR}.', ['foo' => 'apple', 'bar' => 'eye'])
        );
        $this->assertSame(
            'You are the 🍏 to my 👁.',
            Str::placeholders('You are the {FOO} to my {BAR}.', ['foo' => '🍏', 'bar' => '👁'])
        );
        $this->assertSame(
            'You are the {foo} to my {bar}.',
            Str::placeholders('You are the {foo} to my {bar}.', ['foo' => '🍏', 'bar' => '👁'])
        );
        $this->assertSame(
            'You are the 🍏 to my 👁.',
            Str::placeholders('You are the {foo} to my {bar}.', ['foo' => '🍏', 'bar' => '👁'], true)
        );
    }

    public function testTitleCase()
    {
        $this->assertSame(
            'You Are the Apple to My Eye.',
            Str::titleCase('You are the apple to my eye.')
        );
        $this->assertSame(
            'With -- Some Extra-Spacing!',
            Str::titleCase(' with -- some extra-spacing! ')
        );
        $this->assertSame(
            'Het Werkt Ook in het Nederlands',
            Str::titleCase('het werkt ook in het Nederlands', ['de', 'het', 'een', 'en', 'op', 'te', '\'t', 'van', 'den'])
        );
        $this->assertSame(
            'A Test for a Small Word at Start of Line',
            Str::titleCase('a test for a small word at start of line')
        );
        $this->assertSame(
            'Weird Things Happen With Stand-In, or End in "A"',
            Str::titleCase('Weird things happen with stand-in, or end in "a"')
        );
        $this->assertSame(
            'Weird Things Happen With Stand-In, or End in A',
            Str::titleCase('Weird things happen with stand-in, or end in a')
        );
    }
}
