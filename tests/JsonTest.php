<?php

namespace Bolt\Common\Tests;

use Bolt\Common\Exception\DumpException;
use Bolt\Common\Exception\ParseException;
use Bolt\Common\Json;
use Bolt\Common\Tests\Fixtures\TestStringable;
use PHPUnit\Framework\TestCase;
use PHPUnit_Extension_FunctionMocker as FunctionMocker;

class JsonTest extends TestCase
{
    public function testParseNull()
    {
        $this->assertNull(Json::parse(null));
    }

    public function testParseValid()
    {
        $this->assertEquals(['foo' => 'bar'], Json::parse('{"foo": "bar"}'));
    }

    public function testParseErrorDetectExtraComma()
    {
        $json = '{
        "foo": "bar",
}';
        $this->expectParseException($json, 2, 'It appears you have an extra trailing comma');
    }

    public function testParseErrorDetectExtraCommaInArray()
    {
        $json = '{
        "foo": [
            "bar",
        ]
}';
        $this->expectParseException($json, 3, 'It appears you have an extra trailing comma');
    }

    public function testParseErrorDetectUnescapedBackslash()
    {
        $json = '{
        "fo\o": "bar"
}';
        $this->expectParseException($json, 1, 'Invalid string, it appears you have an unescaped backslash');
    }

    public function testParseErrorSkipsEscapedBackslash()
    {
        $json = '{
        "fo\\\\o": "bar"
        "a": "b"
}';
        $this->expectParseException($json, 2);
    }

    public function testParseErrorDetectMissingQuotes()
    {
        $json = '{
        foo: "bar"
}';
        $this->expectParseException($json, 1);
    }

    public function testParseErrorDetectArrayAsHash()
    {
        $json = '{
        "foo": ["bar": "baz"]
}';
        $this->expectParseException($json, 2);
    }

    public function testParseErrorDetectMissingComma()
    {
        $json = '{
        "foo": "bar"
        "bar": "foo"
}';
        $this->expectParseException($json, 2);
    }

    public function testParseErrorDetectMissingCommaMultiline()
    {
        $json = '{
        "foo": "barbar"
        "bar": "foo"
}';
        $this->expectParseException($json, 2);
    }

    public function testParseErrorDetectMissingColon()
    {
        $json = '{
        "foo": "bar",
        "bar" "foo"
}';
        $this->expectParseException($json, 3);
    }

    public function testParseErrorUtf8()
    {
        $json = "{\"message\": \"\xA4\xA6\xA8\xB4\xB8\xBC\xBD\xBE\"}";
        $this->expectParseException($json, -1, 'Malformed UTF-8 characters, possibly incorrectly encoded');
    }

    private function expectParseException($json, $line, $text = null)
    {
        try {
            $result = Json::parse($json);
            $this->fail(sprintf(
                "Parsing should have failed but didn't.\nExpected:\n\"%s\"\nFor:\n\"%s\"\nGot:\n\"%s\"",
                $text,
                $json,
                var_export($result, true)
            ));
        } catch (ParseException $e) {
            $this->assertSame($line, $e->getParsedLine());
            $actualMsg = $e->getMessage();
            $this->assertStringStartsWith('JSON parsing failed: ', $actualMsg);
            $actualMsg = substr($actualMsg, 21);
            if ($text) {
                $this->assertStringStartsWith($text, $actualMsg);
            }
        }
    }

    public function testParseExceptionGettersSetters()
    {
        $ex = new ParseException('Uh oh.');
        $ex->setParsedLine(5);
        $ex->setSnippet('foo bar');

        $this->assertEquals(5, $ex->getParsedLine());
        $this->assertEquals('foo bar', $ex->getSnippet());
        $this->assertEquals('Uh oh at line 5 (near "foo bar").', $ex->getMessage());
    }

    public function testDumpSimpleJsonString()
    {
        $data = ['name' => 'composer/composer'];
        $json = '{
    "name": "composer/composer"
}';
        $this->assertJsonFormat($json, $data);
    }

    public function testDumpTrailingBackslash()
    {
        $data = ['Metadata\\' => 'src/'];
        $json = '{
    "Metadata\\\\": "src/"
}';
        $this->assertJsonFormat($json, $data);
    }

    public function testDumpEscape()
    {
        $data = ['Metadata\\"' => 'src/'];
        $json = '{
    "Metadata\\\\\\"": "src/"
}';
        $this->assertJsonFormat($json, $data);
    }

    public function testDumpUnicode()
    {
        if (!function_exists('mb_convert_encoding')) {
            $this->markTestSkipped('Test requires the mbstring extension');
        }

        $data = ['Žluťoučký " kůň' => 'úpěl ďábelské ódy za €'];
        $json = '{
    "Žluťoučký \" kůň": "úpěl ďábelské ódy za €"
}';
        $this->assertJsonFormat($json, $data);
    }

    public function testDumpOnlyUnicode()
    {
        if (!function_exists('mb_convert_encoding')) {
            $this->markTestSkipped('Test requires the mbstring extension');
        }

        $data = '\\/ƌ';
        $this->assertJsonFormat('"\\\\\\/ƌ"', $data, JSON_UNESCAPED_UNICODE);
    }

    public function testDumpEscapedSlashes()
    {
        $data = '\\/foo';
        $this->assertJsonFormat('"\\\\\\/foo"', $data, 0);
    }

    public function testDumpEscapedBackslashes()
    {
        $data = 'a\\b';
        $this->assertJsonFormat('"a\\\\b"', $data, 0);
    }

    public function testDumpEscapedUnicode()
    {
        $data = 'ƌ';
        $this->assertJsonFormat('"\\u018c"', $data, 0);
    }

    private function assertJsonFormat($json, $data, $options = null)
    {
        if ($options === null) {
            $this->assertEquals($json, Json::dump($data));
        } else {
            $this->assertEquals($json, Json::dump($data, $options));
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testDumpFail()
    {
        $mock = FunctionMocker::start($this, 'Bolt\Common')
            ->mockFunction('json_encode')
            ->mockFunction('json_last_error_msg')
            ->getMock()
        ;
        $mock
            ->expects($this->once())
            ->method('json_encode')
            ->willReturn(false)
        ;
        $mock
            ->expects($this->once())
            ->method('json_last_error_msg')
            ->willReturn('Unknown error')
        ;

        $this->setExpectedException(DumpException::class, 'JSON dumping failed: Unknown error');

        try {
            Json::dump('');
        } finally {
            FunctionMocker::tearDown();
        }
    }

    public function testTest()
    {
        $this->assertFalse(Json::test(null));
        $this->assertFalse(Json::test(123));
        $this->assertTrue(Json::test('{}'));
        $this->assertTrue(Json::test(new TestStringable('{}')));

        $this->assertFalse(Json::test('{"foo": "bar",}'), 'Invalid JSON should return false');
    }
}
