<?php

namespace Bolt\Common\Tests\Fixtures;

class TestColumn
{
    public $id;
    private $value;

    public function __construct($id, $value)
    {
        $this->id = $id;
        $this->value = $value;
    }

    public function __isset($name)
    {
        return $name === 'value';
    }

    public function __get($name)
    {
        return $this->value;
    }
}
