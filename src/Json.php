<?php

namespace Bolt\Common;

use Bolt\Common\Exception\DumpException;
use Bolt\Common\Exception\ParseException;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;

/**
 * JSON parsing and dumping with error handling.
 */
final class Json
{
    /**
     * Dump JSON easy to read for humans.
     * Shortcut for JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE.
     */
    const HUMAN = 448;

    /**
     * Dump JSON without escaping slashes or unicode.
     * Shortcut for JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE.
     */
    const UNESCAPED = 320;

    /**
     * Dump JSON safe for HTML.
     * Shortcut for JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT.
     */
    const HTML = 15;

    /**
     * Dumps a array/object into a JSON string.
     *
     * @param mixed $data    Data to encode into a formatted JSON string
     * @param int   $options Bitmask of JSON encode options
     *                       (defaults to JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
     * @param int   $depth   Set the maximum depth. Must be greater than zero.
     *
     * @throws DumpException If dumping fails
     *
     * @return string
     */
    public static function dump($data, $options = self::HUMAN, $depth = 512)
    {
        $json = @json_encode($data, $options, $depth);

        // If UTF-8 error, try to convert and try again before failing.
        if ($json === false && json_last_error() === JSON_ERROR_UTF8) {
            static::detectAndCleanUtf8($data);

            $json = @json_encode($data, $options, $depth);
        }

        if ($json !== false) {
            // Match PHP 7.1 functionality
            // Escape line terminators with JSON_UNESCAPED_UNICODE unless JSON_UNESCAPED_LINE_TERMINATORS is given
            if (PHP_VERSION_ID < 70100 && $options & JSON_UNESCAPED_UNICODE && ($options & 2048) === 0) {
                $json = str_replace("\xe2\x80\xa8", '\\u2028', $json);
                $json = str_replace("\xe2\x80\xa9", '\\u2029', $json);
            }

            return $json;
        }

        throw new DumpException(sprintf('JSON dumping failed: %s', json_last_error_msg()), json_last_error());
    }

    /**
     * Parses JSON into a PHP array.
     *
     * @param string $json    The JSON string
     * @param int    $options Bitmask of JSON decode options
     * @param int    $depth   Recursion depth
     *
     * @throws ParseException If the JSON is not valid
     *
     * @return array
     */
    public static function parse($json, $options = 0, $depth = 512)
    {
        if ($json === null) {
            return null;
        }

        $json = (string) $json;

        $data = @json_decode($json, true, $depth, $options);

        if ($data === null && ($json === '' || ($code = json_last_error()) !== JSON_ERROR_NONE)) {
            if (isset($code) && ($code === JSON_ERROR_UTF8 || $code === JSON_ERROR_DEPTH)) {
                throw new ParseException(sprintf('JSON parsing failed: %s', json_last_error_msg()), -1, null, $code);
            }

            try {
                (new JsonParser())->parse($json);
            } catch (ParsingException $e) {
                throw ParseException::castFromJson($e);
            }
        }

        return $data;
    }

    /**
     * Return whether the given string is JSON.
     *
     * @param mixed $json
     *
     * @return bool
     */
    public static function test($json)
    {
        if (!is_string($json) && !is_callable([$json, '__toString'])) {
            return false;
        }

        $json = (string) $json;

        // valid for PHP 5.x, invalid for PHP 7.x
        if ($json === '') {
            return false;
        }

        // Don't call our parse(), because we don't need the extra syntax checking.
        @json_decode($json);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Detect invalid UTF-8 string characters and convert to valid UTF-8.
     *
     * Valid UTF-8 input will be left unmodified, but strings containing
     * invalid UTF-8 code-points will be re-encoded as UTF-8 with an assumed
     * original encoding of ISO-8859-15. This conversion may result in
     * incorrect output if the actual encoding was not ISO-8859-15, but it
     * will be clean UTF-8 output and will not rely on expensive and fragile
     * detection algorithms.
     *
     * Function converts the input in place in the passed variable so that it
     * can be used as a callback for array_walk_recursive.
     *
     * @param mixed $data Input to check and convert if needed
     *
     * @see https://github.com/Seldaek/monolog/pull/683
     */
    private static function detectAndCleanUtf8(&$data)
    {
        if ($data instanceof \JsonSerializable) {
            $data = $data->jsonSerialize();
        } elseif ($data instanceof \ArrayObject || $data instanceof \ArrayIterator) {
            $data = $data->getArrayCopy();
        } elseif ($data instanceof \stdClass) {
            $data = (array) $data;
        }
        if (is_array($data)) {
            array_walk_recursive($data, [static::class, 'detectAndCleanUtf8']);

            return;
        }
        if (!is_string($data) || preg_match('//u', $data)) {
            return;
        }
        $data = preg_replace_callback(
            '/[\x80-\xFF]+/',
            function ($m) { return utf8_encode($m[0]); },
            $data
        );
        $data = str_replace(
            ['¤', '¦', '¨', '´', '¸', '¼', '½', '¾'],
            ['€', 'Š', 'š', 'Ž', 'ž', 'Œ', 'œ', 'Ÿ'],
            $data
        );
    }
}
