<?php

namespace Bolt\Common\Tests;

use Bolt\Common\Exception\DumpException;
use Bolt\Common\Exception\ParseException;
use Bolt\Common\Json;
use Bolt\Common\Tests\Fixtures\JsonMocker;
use Bolt\Common\Tests\Fixtures\TestJsonable;
use Bolt\Common\Tests\Fixtures\TestStringable;
use PHPUnit\Framework\TestCase;

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

    public function testParseErrorEmptyString()
    {
        $this->expectParseException('', 0);
    }

    public function testParseErrorObjectEmptyString()
    {
        $this->expectParseException(new TestStringable(''), 0);
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
        $this->expectParseException($json, -1, 'Malformed UTF-8 characters, possibly incorrectly encoded', JSON_ERROR_UTF8);
    }

    private function expectParseException($json, $line, $text = null, $code = JSON_ERROR_SYNTAX)
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
            $this->assertSame($code, $e->getCode());
            $actualMsg = $e->getMessage();
            $this->assertStringStartsWith('JSON parsing failed: ', $actualMsg);
            $actualMsg = substr($actualMsg, 21);
            if ($text) {
                $this->assertStringStartsWith($text, $actualMsg);
            }
        }
    }

    /**
     * @expectedException \Bolt\Common\Exception\ParseException
     * @expectedExceptionMessage JSON parsing failed: Maximum stack depth exceeded
     */
    public function testParseErrorDepth()
    {
        Json::parse('[[["hi"]]]', 0, 1);
    }

    public function testParseExceptionGettersSetters()
    {
        $ex = new ParseException('Uh oh.');
        $ex->setRawMessage('Whoops.');
        $ex->setParsedLine(5);
        $ex->setSnippet('foo bar');

        $this->assertEquals('Whoops.', $ex->getRawMessage());
        $this->assertEquals(5, $ex->getParsedLine());
        $this->assertEquals('foo bar', $ex->getSnippet());
        $this->assertEquals('Whoops at line 5 (near "foo bar").', $ex->getMessage());
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

    public function testDumpConvertsInvalidEncodingAsLatin9()
    {
        $data = "\xA4\xA6\xA8\xB4\xB8\xBC\xBD\xBE";
        $this->assertJsonFormat('"€ŠšŽžŒœŸ"', $data);

        $data = [
            'foo' => new TestJsonable([
                new \ArrayObject(["\xA4"]),
                new \ArrayIterator(["\xA6"]),
                (object) ["\xA8"],
            ]),
            'bar' => 4,
        ];
        $this->assertJsonFormat('{"foo":[["€"],["Š"],["š"]],"bar":4}', $data, JSON_UNESCAPED_UNICODE);
    }

    public function testDumpThrowsCorrectErrorAfterFixingUtf8Error()
    {
        try {
            Json::dump([["\xA4"]], 448, 1);
        } catch (DumpException $e) {
            if ($e->getCode() !== JSON_ERROR_DEPTH) {
                $this->fail('Should have thrown exception with code for max depth');
            }
            $this->assertSame('JSON dumping failed: Maximum stack depth exceeded', $e->getMessage());

            return;
        }

        $this->fail('Should have thrown ' . DumpException::class);
    }

    private function assertJsonFormat($json, $data, $options = null)
    {
        if ($options === null) {
            $this->assertEquals($json, Json::dump($data));
        } else {
            $this->assertEquals($json, Json::dump($data, $options));
        }
    }

    public function testDumpFail()
    {
        $mocker = JsonMocker::instance();
        $mocker->setEncoder(function () {
            return false;
        });
        $mocker->setLastMessageGetter(function () {
            return 'Unknown error';
        });

        $this->setExpectedException(DumpException::class, 'JSON dumping failed: Unknown error');

        try {
            Json::dump('');
        } finally {
            $mocker->reset();
        }
    }

    public function testTest()
    {
        $this->assertFalse(Json::test(null));
        $this->assertFalse(Json::test(123));
        $this->assertFalse(Json::test(''));
        $this->assertFalse(Json::test(new TestStringable('')));
        $this->assertTrue(Json::test('{}'));
        $this->assertTrue(Json::test(new TestStringable('{}')));

        $this->assertFalse(Json::test('{"foo": "bar",}'), 'Invalid JSON should return false');
    }
}
