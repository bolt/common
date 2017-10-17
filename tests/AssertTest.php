<?php

namespace Bolt\Common\Tests;

use Bolt\Common\Assert;

class AssertTest extends TestCase
{
    public function testIsArrayAccessible()
    {
        Assert::isArrayAccessible([1, 2, 3]);
        Assert::isArrayAccessible(new \ArrayObject([1, 2, 3]));

        $this->addToAssertionCount(2);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsArrayAccessibleFailsScalar()
    {
        Assert::isArrayAccessible(123);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsArrayAccessibleFailsObject()
    {
        Assert::isArrayAccessible(new \stdClass());
    }

    public function testIsInstanceOfAny()
    {
        Assert::isInstanceOfAny(new \ArrayIterator(), [\Iterator::class, \ArrayAccess::class]); // both
        Assert::isInstanceOfAny(new \Exception(), [\Exception::class, \Countable::class]); // one of

        $this->addToAssertionCount(2);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsInstanceOfAnyFailsScalar()
    {
        Assert::isInstanceOfAny(new \Exception(), [\ArrayAccess::class, \Countable::class]); // neither
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsInstanceOfAnyFailsObject()
    {
        Assert::isInstanceOfAny([], [\stdClass::class]); // scalar
    }

    public function testIsIterable()
    {
        Assert::isIterable([1, 2, 3]);
        Assert::isIterable(new \ArrayObject([1, 2, 3]));

        $this->addToAssertionCount(2);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsIterableFailsScalar()
    {
        Assert::isIterable(123);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsIterableFailsObject()
    {
        Assert::isIterable(new \stdClass());
    }

    public function testValueToString()
    {
        $this->assertSame('"foo"', Assert::valueToString('foo'));
    }
}
