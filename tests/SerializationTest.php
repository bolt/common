<?php

namespace Bolt\Common\Tests;

use Bolt\Common\Exception\DumpException;
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

    public function testDumpInvalid()
    {
        if (!defined('HHVM_VERSION')) {
            $message = "/Error serializing value\. Serialization of 'Closure' is not allowed/";
        } else {
            $message = '/Error serializing value\. Attempted to serialize unserializable builtin class Closure\$Bolt\\\\Common\\\\Tests\\\\SerializationTest::testDumpInvalid;\d+/';
        }
        $this->setExpectedExceptionRegExp(DumpException::class, $message);

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
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped(
                'HHVM has not implemented "unserialize_callback_func", meaning ' .
                '__PHP_Incomplete_Class could be returned at any level and we are not going to look for them.'
            );
        }

        Serialization::parse('O:38:"ThisClassShouldNotExistsDueToDropBears":0:{}');
    }
}
