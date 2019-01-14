<?php

declare(strict_types=1);

namespace Bolt\Common;

class Str
{
    /**
     * Replaces the first occurrence of the $search text on the $subject.
     */
    public static function replaceFirst(string $subject, string $search, string $replace, bool $caseSensitive = true): string
    {
        $pos = $caseSensitive ? mb_strpos($subject, $search) : mb_stripos($subject, $search);
        if ($pos === false) {
            return $subject;
        }

        return substr_replace($subject, $replace, $pos, \mb_strlen($search));
    }

    /**
     * Replaces the last occurrence of the $search text on the $subject.
     */
    public static function replaceLast(string $subject, string $search, string $replace, bool $caseSensitive = true): string
    {
        $pos = $caseSensitive ? mb_strrpos($subject, $search) : mb_strripos($subject, $search);
        if ($pos === false) {
            return $subject;
        }

        return substr_replace($subject, $replace, $pos, \mb_strlen($search));
    }

    /**
     * Removes the first occurrence of the $search text on the $subject.
     */
    public static function removeFirst(string $subject, string $search, bool $caseSensitive = true): string
    {
        return static::replaceFirst($subject, $search, '', $caseSensitive);
    }

    /**
     * Removes the last occurrence of the $search text on the $subject.
     */
    public static function removeLast(string $subject, string $search, bool $caseSensitive = true): string
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
     */
    public static function splitFirst(string $subject, string $delimiter): string
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
     */
    public static function splitLast(string $subject, string $delimiter): string
    {
        Assert::notEmpty($delimiter);

        $parts = explode($delimiter, $subject);

        return end($parts);
    }

    /**
     * Returns whether the subjects ends with the search string.
     */
    public static function endsWith(string $subject, string $search, bool $caseSensitive = true): bool
    {
        if (! $caseSensitive) {
            $subject = mb_strtolower($subject);
            $search = mb_strtolower($search);
        }

        return $search === '' || mb_substr($subject, -\mb_strlen($search)) === $search;
    }

    /**
     * Returns the class name without the namespace.
     *
     * @param string|object $class object or fully qualified class name
     */
    public static function className($class): string
    {
        if (\is_object($class)) {
            $class = \get_class($class);
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
    public static function humanize(string $text): string
    {
        return ucfirst(trim(mb_strtolower(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $text))));
    }

    /**
     * Converts a string from snake case to camel case.
     *
     * @param string $text               The text to camel case
     * @param bool   $lowercaseFirstChar Whether to lowercase the first character. 'fooBar' vs 'FooBar'.
     *
     * @return string The camel cased text
     */
    public static function camelCase(string $text, bool $lowercaseFirstChar = false): string
    {
        $text = strtr(ucwords(strtr($text, [
            '_' => ' ',
            '.' => '_ ',
            '\\' => '_ ',
        ])), [' ' => '']);
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
    public static function snakeCase(string $text): string
    {
        return mb_strtolower(
            preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], $text)
        );
    }
}
