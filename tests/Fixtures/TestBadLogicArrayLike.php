<?php

namespace Bolt\Common\Tests\Fixtures;

class TestBadLogicArrayLike extends TestArrayLike
{
    public function &offsetGet($offset)
    {
        // Bad: value isn't assigned by reference
        $value = $this->items[$offset];

        return $value;
    }
}
