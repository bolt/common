<?php

declare(strict_types=1);

namespace Bolt\Common\Tests\Fixtures;

class TestBadReferenceExpressionArrayLike extends TestArrayLike
{
    public function &offsetGet($offset)
    {
        // Bad: Only variable references should be returned by reference
        return $this->items[$offset] ?? null;
    }
}
