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
    public static function dump($data, $options = 448, $depth = 512)
    {
        $json = @json_encode($data, $options, $depth);

        if ($json === false) {
            throw new DumpException(sprintf('JSON dumping failed: %s', json_last_error_msg()));
        }

        return $json;
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

        $data = @json_decode($json, true, $depth, $options);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            if (json_last_error() === JSON_ERROR_UTF8) {
                throw new ParseException(sprintf('JSON parsing failed: %s', json_last_error_msg()));
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

        // Don't call our parse(), because we don't need the extra syntax checking.
        @json_decode((string) $json);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
