<?php

declare(strict_types=1);

namespace Bolt\Common\Tests\Fixtures;

class TestBadLogicArrayLike extends TestArrayLike
{
    public function &offsetGet($offset)
    {
        // Bad: value isn't assigned by reference
        return $this->items[$offset];
    }
}
