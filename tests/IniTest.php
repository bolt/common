<?php

namespace Bolt\Common\Tests;

use Bolt\Common\Ini;
use PHPUnit\Framework\TestCase;

class IniTest extends TestCase
{
    const STR_KEY = 'session.save_path';
    const BOOL_KEY = 'assert.bail';
    const INT_KEY = 'precision';
    const READ_ONLY_KEY = 'allow_url_fopen';
    const TRIGGERS_ERROR_KEY = 'session.name';
    const NONEXISTENT_KEY = 'herp.derp';

    private $backup;

    protected function setUp()
    {
        $this->backup = [
            static::STR_KEY  => ini_get(static::STR_KEY),
            static::BOOL_KEY => ini_get(static::BOOL_KEY),
            static::INT_KEY  => ini_get(static::INT_KEY),
        ];
    }

    protected function tearDown()
    {
        foreach ($this->backup as $key => $value) {
            ini_set($key, $value);
        }
    }

    public function testHas()
    {
        $this->assertTrue(Ini::has(static::STR_KEY));
        $this->assertFalse(Ini::has(static::NONEXISTENT_KEY));
    }

    public function testGet()
    {
        $this->assertSame(ini_get(static::STR_KEY), Ini::get(static::STR_KEY));
        $this->assertNull(Ini::get(static::NONEXISTENT_KEY));
    }

    public function testGetBool()
    {
        ini_set(static::BOOL_KEY, '0');
        $this->assertFalse(Ini::getBool(static::BOOL_KEY));

        ini_set(static::BOOL_KEY, '1');
        $this->assertTrue(Ini::getBool(static::BOOL_KEY));


        $this->assertNull(Ini::getBool(static::NONEXISTENT_KEY));
    }

    public function testGetNumeric()
    {
        ini_set(static::INT_KEY, '2');
        $this->assertSame(2, Ini::getNumeric(static::INT_KEY));

        ini_set(static::INT_KEY, '3.2');
        $this->assertSame(3.2, Ini::getNumeric(static::INT_KEY));

        $this->assertNull(Ini::getNumeric(static::NONEXISTENT_KEY));
    }

    public function testSet()
    {
        Ini::set(static::STR_KEY, 'foo');
        $this->assertSame('foo', ini_get(static::STR_KEY));
    }

    public function testSetBoolean()
    {
        Ini::set(static::BOOL_KEY, false);
        $this->assertSame('0', ini_get(static::BOOL_KEY));

        Ini::set(static::BOOL_KEY, true);
        $this->assertSame('1', ini_get(static::BOOL_KEY));
    }

    public function testSetInvalidType()
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'ini values must be scalar or null. Got: array'
        );

        Ini::set(static::INT_KEY, []);
    }

    public function testSetInvalidValue()
    {
        $this->setExpectedException(
            \RuntimeException::class,
            sprintf('Unable to change ini option "%s" to -2.', static::INT_KEY)
        );

        Ini::set(static::INT_KEY, -2);
    }

    public function testSetInvalidValueErrorTriggered()
    {
        try {
            Ini::set(static::TRIGGERS_ERROR_KEY, '');
            $this->fail('Exception should be thrown');
        } catch (\Exception $e) {
        }

        $this->assertInstanceOf(
            \ErrorException::class,
            $e->getPrevious(),
            'set() should have caught a triggered error and thrown it as an ErrorException.'
        );
    }

    public function testSetNewKey()
    {
        $this->setExpectedException(
            \RuntimeException::class,
            sprintf('The ini option "%s" does not exist. New ini options cannot be added.', static::NONEXISTENT_KEY)
        );

        Ini::set(static::NONEXISTENT_KEY, 'foo');
    }

    public function testSetUnauthorized()
    {
        $this->setExpectedException(
            \RuntimeException::class,
            sprintf('Unable to change ini option "%s", because it is not editable at runtime.', static::READ_ONLY_KEY)
        );

        Ini::set(static::READ_ONLY_KEY, true);
    }
}
