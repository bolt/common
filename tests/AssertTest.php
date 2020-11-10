<?php

declare(strict_types=1);

namespace Bolt\Common\Tests;

use Bolt\Common\Assert;

class AssertTest extends TestCase
{
    public function testIsArrayAccessible(): void
    {
        Assert::isArrayAccessible([1, 2, 3]);
        Assert::isArrayAccessible(new \ArrayObject([1, 2, 3]));

        $this->addToAssertionCount(2);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsArrayAccessibleFailsScalar(): void
    {
        Assert::isArrayAccessible(123);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsArrayAccessibleFailsObject(): void
    {
        Assert::isArrayAccessible(new \stdClass());
    }

    public function testIsInstanceOfAny(): void
    {
        // both
        Assert::isInstanceOfAny(new \ArrayIterator(), [\Iterator::class, \ArrayAccess::class]);
        // one of
        Assert::isInstanceOfAny(new \Exception(), [\Throwable::class, \Countable::class]);

        $this->addToAssertionCount(2);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsInstanceOfAnyFailsScalar(): void
    {
        // neither
        Assert::isInstanceOfAny(new \Exception(), [\ArrayAccess::class, \Countable::class]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsInstanceOfAnyFailsObject(): void
    {
        // scalar
        Assert::isInstanceOfAny([], [\stdClass::class]);
    }

    public function testIsIterable(): void
    {
        Assert::isIterable([1, 2, 3]);
        Assert::isIterable(new \ArrayObject([1, 2, 3]));

        $this->addToAssertionCount(2);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsIterableFailsScalar(): void
    {
        Assert::isIterable(123);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsIterableFailsObject(): void
    {
        Assert::isIterable(new \stdClass());
    }

    public function testValueToString(): void
    {
        $this->assertSame('"foo"', Assert::valueToString('foo'));
    }
}
