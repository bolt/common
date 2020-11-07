<?php

declare(strict_types=1);

namespace Bolt\Common\Tests;

use Bolt\Common\Arr;
use Bolt\Common\Tests\Fixtures\TestArrayLike;
use Bolt\Common\Tests\Fixtures\TestBadDefinitionArrayLike;
use Bolt\Common\Tests\Fixtures\TestBadLogicArrayLike;
use Bolt\Common\Tests\Fixtures\TestBadReferenceExpressionArrayLike;
use Bolt\Common\Tests\Fixtures\TestColumn;
use PHPUnit\Framework\TestCase;

/**
 * Tests for \Bolt\Common\Arr.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ArrTest extends TestCase
{
    public function provideFrom()
    {
        return [
            'array' => [
                ['foo' => 'bar'],
                ['foo' => 'bar'],
            ],
            'traversable' => [
                new \ArrayIterator(['foo' => 'bar']),
                ['foo' => 'bar'],
            ],
            'null' => [
                null,
                [],
            ],
            'stdClass' => [
                (object) ['foo' => 'bar'],
                ['foo' => 'bar'],
            ],
        ];
    }

    /**
     * @dataProvider provideFrom
     *
     * @param $input
     * @param $expected
     */
    public function testFrom($input, $expected): void
    {
        $this->assertSame($expected, Arr::from($input));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected an iterable. Got: Exception
     */
    public function testFromNonIterable(): void
    {
        Arr::from(new \Exception());
    }

    public function testFromRecursive(): void
    {
        $expected = [
            'foo' => 'bar',
            'colors' => ['red', 'blue'],
            'items' => ['hello', 'world'],
        ];
        $input = (object) [
            'foo' => 'bar',
            'colors' => ['red', 'blue'],
            'items' => (object) ['hello', 'world'],
        ];

        $this->assertSame($expected, Arr::fromRecursive($input));
    }

    public function testColumn(): void
    {
        $data = new \ArrayIterator([
            new TestColumn('foo', 'bar'),
            new TestColumn('hello', 'world'),
            [
                'id' => '5',
                'value' => 'asdf',
            ],
            new \ArrayObject([
                'id' => '6',
                'value' => 'blue',
            ]),
            // skipped if missing column key. Appended if missing index key.
            ['value' => 'no key is appended'],
        ]);

        $result = Arr::column($data, null);
        $this->assertSame($data->getArrayCopy(), $result);

        $result = Arr::column($data, 'id');
        $this->assertSame(['foo', 'hello', '5', '6'], $result);

        $result = Arr::column($data, 'value', 'id');
        $expected = [
            'foo' => 'bar',
            'hello' => 'world',
            '5' => 'asdf',
            '6' => 'blue',
            7 => 'no key is appended',
        ];
        $this->assertSame($expected, $result);
    }

    public function provideGetSetHasInvalidArgs()
    {
        return [
            'data not accessible' => [new \EmptyIterator(), 'foo'],
            // 'path not string' => [[], false],
            'empty path' => [[], ''],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider provideGetSetHasInvalidArgs
     */
    public function testHasInvalidArgs($data, $path): void
    {
        Arr::has($data, $path);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider provideGetSetHasInvalidArgs
     */
    public function testGetInvalidArgs($data, $path): void
    {
        Arr::get($data, $path);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider provideGetSetHasInvalidArgs
     */
    public function testSetInvalidArgs($data, $path): void
    {
        Arr::set($data, $path, 'mixed');
    }

    public function provideGetSetHas()
    {
        return [
            'array' => [
                [
                    'foo' => 'bar',
                    'baz' => 'remove',
                    'items' => [
                        'nested' => [
                            'hello' => 'world',
                            'bye' => 'earth',
                        ],
                        'obj' => new \EmptyIterator(),
                    ],
                ],
            ],

            'array access' => [
                new \ArrayObject([
                    'foo' => 'bar',
                    'baz' => 'remove',
                    'items' => new \ArrayObject([
                        'nested' => new \ArrayObject([
                            'hello' => 'world',
                            'bye' => 'earth',
                        ]),
                        'obj' => new \EmptyIterator(),
                    ]),
                ]),
            ],

            'user array access' => [
                new TestArrayLike([
                    'foo' => 'bar',
                    'baz' => 'remove',
                    'items' => new TestArrayLike([
                        'nested' => new TestArrayLike([
                            'hello' => 'world',
                            'bye' => 'earth',
                        ]),
                        'obj' => new \EmptyIterator(),
                    ]),
                ]),
            ],

            'mixed' => [
                [
                    'foo' => 'bar',
                    'baz' => 'remove',
                    'items' => new \ArrayObject([
                        'nested' => [
                            'hello' => 'world',
                            'bye' => 'earth',
                        ],
                        'obj' => new \EmptyIterator(),
                    ]),
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideGetSetHas
     *
     * @param array|\ArrayAccess $data
     */
    public function testHas($data): void
    {
        $this->assertTrue(Arr::has($data, 'foo'));
        $this->assertTrue(Arr::has($data, 'items'));
        $this->assertTrue(Arr::has($data, 'items/nested/hello'));

        $this->assertFalse(Arr::has($data, 'derp'));
        $this->assertFalse(Arr::has($data, 'items/obj/bad'));
    }

    /**
     * @dataProvider provideGetSetHas
     *
     * @param array|\ArrayAccess $data
     */
    public function testGet($data): void
    {
        $this->assertSame('bar', Arr::get($data, 'foo'));
        $this->assertSame('world', Arr::get($data, 'items/nested/hello'));

        $this->assertSame('default', Arr::get($data, 'derp', 'default'));
        $this->assertSame('default', Arr::get($data, 'items/derp', 'default'));
        $this->assertSame('default', Arr::get($data, 'derp/nope/whoops', 'default'));
    }

    /**
     * @dataProvider provideGetSetHas
     *
     * @param array|\ArrayAccess $data
     */
    public function testSet($data): void
    {
        Arr::set($data, 'color', 'red');
        $this->assertSame('red', $data['color']);

        Arr::set($data, '[]', 'first');
        $this->assertSame('first', $data[0]);
        Arr::set($data, '[]', 'second');
        $this->assertSame('second', $data[1]);

        Arr::set($data, 'items/nested/color', 'blue');
        $this->assertSame('blue', $data['items']['nested']['color']);

        Arr::set($data, 'items/nested/new/point', 'bolt');
        $this->assertSame('bolt', $data['items']['nested']['new']['point']);

        Arr::set($data, 'items/nested/list/[]', 'first');
        $this->assertSame('first', $data['items']['nested']['list'][0]);
        Arr::set($data, 'items/nested/list/[]', 'second');
        $this->assertSame('second', $data['items']['nested']['list'][1]);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot set 'a/foo', because 'a' is already set and not
     *                           an array or an object implementing ArrayAccess.
     */
    public function testSetNestedInaccessibleObject(): void
    {
        $data = [
            'a' => new \EmptyIterator(),
        ];

        Arr::set($data, 'a/foo', 'bar');
    }

    public function provideSetArraysReturnedByReferenceError()
    {
        return [
            'bad definition' => [TestBadDefinitionArrayLike::class],
            'bad logic' => [TestBadLogicArrayLike::class],
            'bad expression' => [TestBadReferenceExpressionArrayLike::class],
        ];
    }

    /**
     * Test that Arr::set can determine which objects can return arrays by reference.
     *
     * Test that Arr::set throws exception when trying to indirectly modify an ArrayAccess object.
     * This happens when one tries get a reference to an array inside an AA object.
     * Ex: A/B/C where A is an AA object. B is an array.
     *
     * @dataProvider provideSetArraysReturnedByReferenceError
     *
     * @param string $cls
     */
    public function testSetArraysReturnedByReferenceError($cls): void
    {
        $data = [
            'a' => new $cls(),
        ];

        $e = null;
        try {
            Arr::set($data, 'a/foo/bar', 'baz');
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }

        dump($e);

//        if ($e instanceof \RuntimeException) {
//            $this->assertSame(
//                "Cannot set 'a/foo/bar', because 'a' is an " . ltrim($cls, '\\') .
//                ' which does not return arrays by reference from its offsetGet() method.',
//                $e->getMessage()
//            );
//        } else {
//            $this->fail("Arr::set should've thrown a RuntimeException");
//        }
    }

    /**
     * @dataProvider provideGetSetHas
     *
     * @param array|\ArrayAccess $data
     */
    public function testRemove($data): void
    {
        $this->assertSame('remove', Arr::remove($data, 'baz', 'default'));
        $this->assertSame('default', Arr::remove($data, 'baz', 'default'));

        $this->assertSame('earth', Arr::remove($data, 'items/nested/bye', 'default'));
        $this->assertSame('default', Arr::remove($data, 'items/nested/bye', 'default'));
    }

    public function testIsAccessible(): void
    {
        $this->assertTrue(Arr::isAccessible([]));
        $this->assertTrue(Arr::isAccessible(new \ArrayObject()));

        $this->assertFalse(Arr::isAccessible(new \EmptyIterator()));
    }

    /**
     * @group legacy
     */
    public function testAssertAccessible(): void
    {
        $e = null;

        try {
            Arr::assertAccessible([]);
            Arr::assertAccessible(new \ArrayObject());
        } catch (\Throwable $e) {
        }

        $this->assertNull($e);
    }

    /**
     * @group legacy
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected an array accessible. Got: EmptyIterator
     */
    public function testAssertAccessibleFail(): void
    {
        Arr::assertAccessible(new \EmptyIterator());
    }

    public function provideIsIndexed()
    {
        return [
            'key value pairs' => [['key' => 'value'], false],
            'empty array' => [[], true],
            'list' => [['foo', 'bar'], true],
            'zero-indexed numeric int keys' => [[
                0 => 'foo',
                1 => 'bar',
            ], true],
            'zero-indexed numeric string keys' => [[
                '0' => 'foo',
                '1' => 'bar',
            ], true],
            'non-zero-indexed keys' => [[
                1 => 'foo',
                2 => 'bar',
            ], false],
            'non-sequential keys' => [[
                0 => 'foo',
                2 => 'bar',
            ], false],
        ];
    }

    /**
     * @dataProvider provideIsIndexed
     *
     * @param array $array
     * @param bool $indexed
     */
    public function testIsIndexedAndAssociative($array, $indexed): void
    {
        $this->assertSame($indexed, Arr::isIndexed($array));
        $this->assertSame(! $indexed, Arr::isAssociative($array));

        $traversable = new \ArrayObject($array);
        $this->assertSame($indexed, Arr::isIndexed($traversable));
        $this->assertSame(! $indexed, Arr::isAssociative($traversable));
    }

    public function testNonArraysAreNotIndexedOrAssociative(): void
    {
        $this->assertFalse(Arr::isIndexed('derp'));
        $this->assertFalse(Arr::isAssociative('derp'));
    }

    public function testMapRecursive(): void
    {
        $arr = [
            'foo' => new \ArrayObject([
                'bar' => 'HELLO',
            ]),
        ];

        $actual = Arr::mapRecursive($arr, 'strtolower');

        $expected = [
            'foo' => [
                'bar' => 'hello',
            ],
        ];

        $this->assertSame($expected, $actual);
    }

    public function provideReplaceRecursive()
    {
        return [
            'scalar replaces scalar (no duh)' => [
                [
                    'a' => [
                        'b' => 'foo',
                    ],
                ],
                [
                    'a' => [
                        'b' => 'bar',
                    ],
                ],
                [
                    'a' => [
                        'b' => 'bar',
                    ],
                ],
            ],
            'second adds to first (no duh)' => [
                [
                    'a' => [
                        'b' => 'foo',
                    ],
                ],
                [
                    'a' => [
                        'c' => 'bar',
                    ],
                ],
                [
                    'a' => [
                        'b' => 'foo',
                        'c' => 'bar',
                    ],
                ],
            ],
            'list replaces list completely' => [
                [
                    'a' => ['foo', 'bar'],
                ],
                [
                    'a' => ['baz'],
                ],
                [
                    'a' => ['baz'],
                ],
            ],
            'null replaces scalar' => [
                [
                    'a' => [
                        'b' => 'foo',
                    ],
                ],
                [
                    'a' => [
                        'b' => null,
                    ],
                ],
                [
                    'a' => [
                        'b' => null,
                    ],
                ],
            ],
            'null ignores arrays (both types)' => [
                [
                    'a' => [
                        'b' => 'foo',
                    ],
                ],
                ['a' => null],
                [
                    'a' => [
                        'b' => 'foo',
                    ],
                ],
            ],
            'empty list replaces arrays (both types)' => [
                [
                    'a' => ['foo', 'bar'],
                ],
                [
                    'a' => [],
                ],
                [
                    'a' => [],
                ],
            ],
            'scalar replaces arrays (both types)' => [
                [
                    'a' => ['foo', 'bar'],
                ],
                ['a' => 'derp'],
                ['a' => 'derp'],
            ],
            'traversable' => [
                new \ArrayObject([
                    'a' => new \ArrayObject([
                        'foo' => 'bar',
                        'hello' => 'world',
                        'dont override' => new \ArrayObject(['for reals']),
                    ]),
                ]),
                new \ArrayObject([
                    'a' => new \ArrayObject([
                        'foo' => 'baz',
                        'dont override' => null,
                    ]),
                ]),
                [
                    'a' => [
                        // replaced value
                        'foo' => 'baz',
                        // untouched pair
                        'hello' => 'world',
                        // null didn't overwrite list
                        'dont override' => ['for reals'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideReplaceRecursive
     *
     * @param array $array1
     * @param array $array2
     * @param array $result
     */
    public function testReplaceRecursive($array1, $array2, $result): void
    {
        $this->assertSame($result, Arr::replaceRecursive($array1, $array2));
    }

    public function testFlatten(): void
    {
        $result = Arr::flatten([[1, 2], [[3]], 4]);

        $this->assertSame([1, 2, [3], 4], $result);
    }

    public function testFlattenDeep(): void
    {
        $result = Arr::flatten([[1, 2], [[3]], 4], INF);

        $this->assertSame([1, 2, 3, 4], $result);
    }
}
