<?php

declare(strict_types=1);

namespace Bolt\Common\Tests\Fixtures;

class TestJsonable implements \JsonSerializable
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function jsonSerialize()
    {
        return $this->data;
    }
}
