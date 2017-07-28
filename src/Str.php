<?php

namespace Bolt\Common;

class Str
{
    /**
     * Replaces the first occurrence of the $search text on the $subject.
     *
     * @param string $subject
     * @param string $search
     * @param string $replace
     * @param bool   $caseSensitive
     *
     * @return string
     */
    public static function replaceFirst($subject, $search, $replace, $caseSensitive = true)
    {
        $pos = $caseSensitive ? strpos($subject, $search) : stripos($subject, $search);
        if ($pos === false) {
            return $subject;
        }

        return substr_replace($subject, $replace, $pos, strlen($search));
    }

    /**
     * Replaces the last occurrence of the $search text on the $subject.
     *
     * @param string $subject
     * @param string $search
     * @param string $replace
     * @param bool   $caseSensitive
     *
     * @return string
     */
    public static function replaceLast($subject, $search, $replace, $caseSensitive = true)
    {
        $pos = $caseSensitive ? strrpos($subject, $search) : strripos($subject, $search);
        if ($pos === false) {
            return $subject;
        }

        return substr_replace($subject, $replace, $pos, strlen($search));
    }

    /**
     * Removes the first occurrence of the $search text on the $subject.
     *
     * @param string $subject
     * @param string $search
     * @param bool   $caseSensitive
     *
     * @return string
     */
    public static function removeFirst($subject, $search, $caseSensitive = true)
    {
        return static::replaceFirst($subject, $search, '', $caseSensitive);
    }

    /**
     * Removes the last occurrence of the $search text on the $subject.
     *
     * @param string $subject
     * @param string $search
     * @param bool   $caseSensitive
     *
     * @return string
     */
    public static function removeLast($subject, $search, $caseSensitive = true)
    {
        return static::replaceLast($subject, $search, '', $caseSensitive);
    }

    /**
     * Splits a $subject on the $delimiter and returns the first part.
     * If the delimiter is not found in the string the string is returned.
     *
     * @param string $subject   The string to split
     * @param string $delimiter The term to split on
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public static function splitFirst($subject, $delimiter)
    {
        Assert::notEmpty($delimiter);

        $parts = explode($delimiter, $subject, 2);

        return reset($parts);
    }

    /**
     * Splits a $subject on the $delimiter and returns the last part.
     * If the delimiter is not found in the string the string is returned.
     *
     * @param string $subject   The string to split
     * @param string $delimiter The term to split on
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public static function splitLast($subject, $delimiter)
    {
        Assert::notEmpty($delimiter);

        $parts = explode($delimiter, $subject);

        return end($parts);
    }

    /**
     * Returns whether the subjects ends with the search string.
     *
     * @param string $subject
     * @param string $search
     * @param bool   $caseSensitive
     *
     * @return bool
     */
    public static function endsWith($subject, $search, $caseSensitive = true)
    {
        if (!$caseSensitive) {
            $subject = strtolower($subject);
            $search = strtolower($search);
        }

        return $search === '' || substr($subject, -strlen($search)) === $search;
    }

    /**
     * Returns the class name without the namespace.
     *
     * @param string|object $class object or fully qualified class name
     *
     * @return string
     */
    public static function className($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        return static::splitLast($class, '\\');
    }

    /**
     * Makes a technical name human readable.
     *
     * Sequences of snake cased or camel cased are replaced by single spaces.
     * The first letter of the resulting string is capitalized,
     * while all other letters are turned to lowercase.
     *
     * @param string $text The text to humanize
     *
     * @return string The humanized text
     */
    public static function humanize($text)
    {
        return ucfirst(trim(strtolower(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $text))));
    }

    /**
     * Converts a string from snake case to camel case.
     *
     * @param string $text               The text to camel case
     * @param bool   $lowercaseFirstChar Whether to lowercase the first character. 'fooBar' vs 'FooBar'.
     *
     * @return string The camel cased text
     */
    public static function camelCase($text, $lowercaseFirstChar = false)
    {
        $text = strtr(ucwords(strtr($text, ['_' => ' ', '.' => '_ ', '\\' => '_ '])), [' ' => '']);
        if ($lowercaseFirstChar) {
            $text = lcfirst($text);
        }

        return $text;
    }

    /**
     * Converts a string from camel case to snake case.
     *
     * @param string $text The text to snake case
     *
     * @return string The snake cased text
     */
    public static function snakeCase($text)
    {
        return strtolower(
            preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], $text)
        );
    }
}
