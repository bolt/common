<?php

namespace Bolt\Common\Tests;

use Bolt\Common\Serialization;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Common\Serialization
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SerializationTest extends TestCase
{
    public function testDump()
    {
        $result = Serialization::dump(new \stdClass());
        $this->assertSame(serialize(new \stdClass()), $result);
    }

    /**
     * @expectedException \Bolt\Common\Exception\DumpException
     * @expectedExceptionMessage Error serializing value. Serialization of 'Closure' is not allowed
     */
    public function testDumpInvalid()
    {
        Serialization::dump(function () {});
    }

    public function testParseSimple()
    {
        $result = Serialization::parse(serialize(new \stdClass()));
        $this->assertInstanceOf(\stdClass::class, $result);
    }

    /**
     * @expectedException \Bolt\Common\Exception\ParseException
     * @expectedExceptionMessage Error parsing serialized value.
     */
    public function testParseInvalidData()
    {
        Serialization::parse('O:9:"stdClass":0:{}');
    }

    /**
     * @expectedException \Bolt\Common\Exception\ParseException
     * @expectedExceptionMessage Error parsing serialized value. Could not find class: ThisClassShouldNotExistsDueToDropBears
     */
    public function testParseClassNotFound()
    {
        Serialization::parse('O:38:"ThisClassShouldNotExistsDueToDropBears":0:{}');
    }
}
