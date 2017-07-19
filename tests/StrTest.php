<?php

namespace Bolt\Common\Tests;

use Bolt\Common\Str;
use PHPUnit\Framework\TestCase;

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

        $this->assertSame('asdf', Str::removeFirst('asdf', 'zxc'));
    }

    public function testRemoveLast()
    {
        $this->assertSame('HelloGoodbyeGoodbye', Str::removeLast('HelloGoodbyeHelloGoodbye', 'Hello'));
        $this->assertSame('HelloGoodbyeGoodbye', Str::removeLast('HelloGoodbyeHELLOGoodbye', 'Hello', false));

        $this->assertSame('asdf', Str::removeLast('asdf', 'zxc'));
    }

    public function testSplitFirst()
    {
        $this->assertSame('herp', Str::splitFirst('herp derp terp lerp', ' '));
        $this->assertSame('herp derp', Str::splitFirst('herp derp', ','));
        $this->assertFalse(Str::splitFirst('herp derp', ''));
    }

    public function testSplitLast()
    {
        $this->assertSame('lerp', Str::splitLast('herp derp terp lerp', ' '));
        $this->assertSame('herp derp', Str::splitLast('herp derp', ','));
        $this->assertFalse(Str::splitLast('herp derp', ''));
    }

    public function testEndsWith()
    {
        $this->assertTrue(Str::endsWith('FooBar', 'Bar'));
        $this->assertTrue(Str::endsWith('FooBar', 'bar', false));
        $this->assertFalse(Str::endsWith('FooBar', 'Foo'));
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
}
