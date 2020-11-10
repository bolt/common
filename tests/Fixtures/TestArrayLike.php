<?php

declare(strict_types=1);

namespace Bolt\Common\Tests\Fixtures;

class TestArrayLike implements \ArrayAccess
{
    protected $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    // Note "&"
    public function &offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }
}
