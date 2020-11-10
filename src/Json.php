<?php

declare(strict_types=1);

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
    public const HUMAN = 448;

    /**
     * Dump JSON without escaping slashes or unicode.
     * Shortcut for JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE.
     */
    public const UNESCAPED = 320;

    /**
     * Dump JSON safe for HTML.
     * Shortcut for JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT.
     */
    public const HTML = 15;

    /**
     * Wrapper for json_decode that throws when an error occurs.
     *
     * @param string $json JSON data to parse
     * @param bool $assoc When true, returned objects will be converted
     *                        into associative arrays.
     * @param int $depth User specified recursion depth.
     * @param int $options Bitmask of JSON decode options.
     *
     * @throws \InvalidArgumentException if the JSON cannot be decoded.
     *
     * @see http://www.php.net/manual/en/function.json-decode.php
     */
    public static function json_decode($json, $assoc = false, $depth = 512, $options = 0)
    {
        $data = \json_decode($json, $assoc, $depth, $options);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('json_decode error: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Wrapper for JSON encoding that throws when an error occurs.
     *
     * @param mixed $value The value being encoded
     * @param int $options JSON encode option bitmask
     * @param int $depth Set the maximum depth. Must be greater than zero.
     *
     * @throws \InvalidArgumentException if the JSON cannot be encoded.
     *
     * @see http://www.php.net/manual/en/function.json-encode.php
     */
    public static function json_encode($value, $options = 0, $depth = 512): string
    {
        $json = \json_encode($value, $options, $depth);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('json_encode error: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Dumps a array/object into a JSON string.
     *
     * @param mixed $data Data to encode into a formatted JSON string
     * @param int $options Bitmask of JSON encode options
     *                       (defaults to JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
     * @param int $depth Set the maximum depth. Must be greater than zero.
     *
     * @throws DumpException If dumping fails
     */
    public static function dump($data, $options = self::UNESCAPED, $depth = 512): string
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
            if (\PHP_VERSION_ID < 70100 && $options & JSON_UNESCAPED_UNICODE && ($options & 2048) === 0) {
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
     * @param int $options Bitmask of JSON decode options
     * @param int $depth Recursion depth
     *
     * @throws ParseException If the JSON is not valid
     */
    public static function parse(?string $json = null, int $options = 0, int $depth = 512): ?array
    {
        if ($json === null) {
            return null;
        }

        $json = (string) $json;

        $data = @json_decode($json, true, $depth, $options);

        if ($data === null && ($json === '' || (json_last_error()) !== JSON_ERROR_NONE)) {
            $code = json_last_error();
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
     * Find a scalar value in provided string/json/array.
     *
     * @throws \Exception
     */
    public static function findScalar($input)
    {
        if (is_iterable($input)) {
            return self::findScalar(current($input));
        }

        if (self::test($input)) {
            return self::findScalar(self::parse($input));
        }

        if (! is_scalar($input)) {
            throw new \Exception("Can't find a scalar in provided input");
        }

        return $input;
    }

    /**
     * Find an array in provided string/json/array.
     *
     * @throws \Exception
     */
    public static function findArray($input)
    {
        if (is_iterable($input) && self::test(current($input))) {
            return self::findArray(current($input));
        }

        if (self::test($input)) {
            return self::findArray(self::parse($input));
        }

        if (! is_iterable($input)) {
            throw new \Exception("Can't find an array in provided input");
        }

        return $input;
    }

    /**
     * Return whether the given string is JSON.
     */
    public static function test($json): bool
    {
        if (! \is_string($json) && ! \is_callable([$json, '__toString'])) {
            return false;
        }

        $json = (string) $json;

        // Check if string has `[` or `{`, because otherwise `123` would pass.
        // That's strictly speaking valid JSON, but not what we're looking for.
        if (mb_strpos($json, '[') === false && mb_strpos($json, '{') === false) {
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
    private static function detectAndCleanUtf8(&$data): void
    {
        if ($data instanceof \JsonSerializable) {
            $data = $data->jsonSerialize();
        } elseif ($data instanceof \ArrayObject || $data instanceof \ArrayIterator) {
            $data = $data->getArrayCopy();
        } elseif ($data instanceof \stdClass) {
            $data = (array) $data;
        }
        if (\is_array($data)) {
            array_walk_recursive($data, [static::class, 'detectAndCleanUtf8']);

            return;
        }
        if (! \is_string($data) || preg_match('//u', $data)) {
            return;
        }
        $data = preg_replace_callback(
            '/[\x80-\xFF]+/',
            function ($m) {
                return utf8_encode($m[0]);
            },
            $data
        );
        $data = str_replace(
            ['¤', '¦', '¨', '´', '¸', '¼', '½', '¾'],
            ['€', 'Š', 'š', 'Ž', 'ž', 'Œ', 'œ', 'Ÿ'],
            $data
        );
    }
}
