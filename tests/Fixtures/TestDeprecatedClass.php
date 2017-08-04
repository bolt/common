<?php

namespace Bolt\Common\Tests\Fixtures;

use Bolt\Common\Deprecated;

class TestDeprecatedClass
{
    public function __construct($deprecatedClass = false)
    {
        if ($deprecatedClass) {
            Deprecated::method(null, \ArrayObject::class);
        }
    }

    public static function foo()
    {
        Deprecated::method(null, \ArrayObject::class);
    }

    public function __call($name, $arguments)
    {
        Deprecated::method();
    }

    public static function __callStatic($name, $arguments)
    {
        Deprecated::method();
    }

    public static function getArrayCopy()
    {
        Deprecated::method(null, \ArrayObject::class);
    }

    public static function someMethod()
    {
        static::deprecated();
    }

    private static function deprecated()
    {
        Deprecated::method(null, \ArrayObject::class, 1);
    }
}
