<?php

namespace Bolt\Common\Tests\Fixtures;

use Bolt\Common\Deprecated;

class TestDeprecatedClass
{
    public function __construct($deprecatedClass = false)
    {
        if ($deprecatedClass) {
            Deprecated::method();
        }
    }

    public static function foo()
    {
        Deprecated::method();
    }

    public function __call($name, $arguments)
    {
        Deprecated::method();
    }

    public static function __callStatic($name, $arguments)
    {
        Deprecated::method();
    }

    public function __get($name)
    {
        Deprecated::method();
    }

    public function __set($name, $value)
    {
        Deprecated::method();
    }

    public function __isset($name)
    {
        Deprecated::method();
    }

    public function __unset($name)
    {
        Deprecated::method();
    }
}

function deprecatedFunction()
{
    Deprecated::method();
}
