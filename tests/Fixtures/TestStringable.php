<?php

declare(strict_types=1);

namespace Bolt\Common\Tests\Fixtures;

class TestStringable
{
    private $string;

    public function __construct($string)
    {
        $this->string = $string;
    }

    public function __toString()
    {
        return $this->string;
    }
}
