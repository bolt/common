<?php

declare(strict_types=1);

namespace Bolt\Common;

use Cocur\Slugify\Slugify;

class Str
{
    /**
     * We use Slugify as a Singleton because Slugify::create() is quite heavy
     *
     * @var Slugify
     */
    private static $slugifyInstance = null;

    /** @var Slugify[] */
    private static $slugifySafeInstances = [];

    /**
     * Replaces the first occurrence of the $search text on the $subject.
     */
    public static function replaceFirst(string $subject, string $search, string $replace, bool $caseSensitive = true): string
    {
        $pos = $caseSensitive ? mb_strpos($subject, $search) : mb_stripos($subject, $search);
        if ($pos === false) {
            return $subject;
        }

        return self::mb_substr_replace($subject, $replace, $pos, mb_strlen($search));
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

        return self::mb_substr_replace($subject, $replace, $pos, mb_strlen($search));
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

        return $search === '' || mb_substr($subject, -mb_strlen($search)) === $search;
    }

    /**
     * Returns whether the subjects starts with the search string.
     */
    public static function startsWith(string $subject, string $search, bool $caseSensitive = true): bool
    {
        if (! $caseSensitive) {
            $subject = mb_strtolower($subject);
            $search = mb_strtolower($search);
        }

        return $search === '' || mb_substr($subject, 0, mb_strlen($search)) === $search;
    }

    /**
     * Ensure a string ends with a given string. If it doesn't already end in
     * it, this function will append it.
     */
    public static function ensureEndsWith(string $subject, string $desiredEnd): string
    {
        if (! self::endsWith($subject, $desiredEnd)) {
            $subject .= $desiredEnd;
        }

        return $subject;
    }

    /**
     * Ensure a string starts with a given string. If it doesn't already start
     * with it, this function will prepend it.
     */
    public static function ensureStartsWith(string $subject, string $desiredStart): string
    {
        if (! self::startsWith($subject, $desiredStart)) {
            $subject = $desiredStart . $subject;
        }

        return $subject;
    }

    /**
     * Returns the class name without the namespace.
     *
     * @param string|object $class object or fully qualified class name
     */
    public static function className($class): string
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

    /**
     * Returns a "safe" version of the given string - basically only US-ASCII and
     * numbers. Needed because filenames and titles and such, can't use all characters.
     */
    public static function makeSafe(string $str, bool $strict = false, string $extrachars = ''): string
    {
        $str = str_replace('&amp;', '&', $str);

        $slugify = self::getSafeSlugify($strict, $extrachars);
        $str = $slugify->slugify($str, '');

        if ($strict) {
            $str = str_replace(' ', '-', $str);
        }

        return $str;
    }

    public static function slug(string $str, $options = null): string
    {
        return self::getSlugify()->slugify($str, $options);
    }

    /**
     * Add 'soft hyphens' &shy; to a string, so that it won't break layout in HTML when
     * using strings without spaces or dashes. Only breaks in long (> 19 chars) words.
     */
    public static function shyphenate(string $str): string
    {
        $res = preg_match_all('/([a-z0-9]{19,})/i', $str, $matches);

        if ($res) {
            foreach ($matches[1] as $match) {
                $str = str_replace($match, wordwrap($match, 10, '&shy;', true), $str);
            }
        }

        return $str;
    }

    private static function getSlugify(): Slugify
    {
        if (self::$slugifyInstance === null) {
            self::$slugifyInstance = Slugify::create();
        }
        return self::$slugifyInstance;
    }

    private static function getSafeSlugify(bool $strict = false, string $extrachars = ''): Slugify
    {
        $key = $strict ? 'strict_' : '' . $extrachars;

        if (empty(self::$slugifySafeInstances[$key]) === true) {
            $delim = '/';
            if ($extrachars !== '') {
                $extrachars = preg_quote($extrachars, $delim);
            }
            if ($strict) {
                $slugify = Slugify::create([
                    'regexp' => '/[^a-z0-9_' . $extrachars . ' -]+/',
                ]);
            } else {
                // Allow Uppercase and don't convert spaces to dashes
                $slugify = Slugify::create([
                    'regexp' => '/[^a-zA-Z0-9_.,' . $extrachars . ' -]+/',
                    'lowercase' => false,
                ]);
            }

            self::$slugifySafeInstances[$key] = $slugify;
        }

        return self::$slugifySafeInstances[$key];
    }

    public static function generatePassword($length = 12)
    {
        // The "pool" of potential characters contains special characters, but
        // with lower frequency than 'a-z' and '0-9'.
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' .
            'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' .
            'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' .
            'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' .
            '-=~!@#$%^&*()_+,./<>?;:[]{}\|';

        $str = '';
        $max = mb_strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, $max)];
        }

        return $str;
    }

    /**
     * @see https://gist.github.com/stemar/8287074
     */
    public static function mb_substr_replace($string, $replacement, $start, $length = null)
    {
        if (is_array($string)) {
            $num = count($string);

            // $replacement
            $replacement = is_array($replacement) ? array_slice($replacement, 0, $num) : array_pad([$replacement], $num, $replacement);

            // $start
            if (is_array($start)) {
                $start = array_slice($start, 0, $num);
                foreach ($start as $key => $value) {
                    $start[$key] = is_int($value) ? $value : 0;
                }
            } else {
                $start = array_pad([$start], $num, $start);
            }

            // $length
            if (! isset($length)) {
                $length = array_fill(0, $num, 0);
            } elseif (is_array($length)) {
                $length = array_slice($length, 0, $num);
                foreach ($length as $key => $value) {
                    $length[$key] = isset($value) ? (is_int($value) ? $value : $num) : 0;
                }
            } else {
                $length = array_pad([$length], $num, $length);
            }

            // Recursive call
            return array_map(__FUNCTION__, $string, $replacement, $start, $length);
        }

        preg_match_all('/./us', (string) $string, $smatches);
        preg_match_all('/./us', (string) $replacement, $rmatches);

        if ($length === null) {
            $length = mb_strlen($string);
        }

        array_splice($smatches[0], $start, $length, $rmatches[0]);

        return implode($smatches[0]);
    }

    public static function placeholders(string $string, array $replacements): string
    {
        return preg_replace_callback(
            '/{([A-Z0-9_]+)}/',
            function ($matches) use ($replacements) {
                $key = mb_strtolower($matches[1]);
                return array_key_exists($key, $replacements) ? $replacements[$key] : $matches[0];
            },
            $string
        );
    }

    /**
     * Returns a trimmed string in proper title case.
     *
     * Also accepts an array, $ignore, allowing you to list words not to be
     * capitalized.
     *
     * Adapted from John Gruber's script, as used int `voku/portable-utf8`
     *
     * @see https://gist.github.com/gruber/9f9e8650d68b13ce4d78
     * @see https://github.com/voku/portable-utf8/
     *
     * @param array  $ignore   <p>An array of words not to capitalize.</p>
     * @param string $encoding [optional] <p>Set the charset for e.g. "mb_" function</p>
     *
     * @return string
     *                <p>The titleized string.</p>
     */
    public static function titleCase(
        string $str,
        array $ignore = [],
        string $encoding = 'UTF-8'
    ): string {
        if ($str === '') {
            return '';
        }

        $small_words = [
            '(?<!q&)a',
            'an',
            'and',
            'as',
            'at(?!&t)',
            'but',
            'by',
            'en',
            'for',
            'if',
            'in',
            'of',
            'on',
            'or',
            'the',
            'to',
            'v[.]?',
            'via',
            'vs[.]?',
        ];

        if ($ignore !== []) {
            $small_words = \array_merge($small_words, $ignore);
        }

        $small_words_rx = \implode('|', $small_words);
        $apostrophe_rx = '(?x: [\'’] [[:lower:]]* )?';

        $str = \mb_strtolower(\trim($str));

        // the main substitutions
        /** @noinspection RegExpDuplicateAlternationBranch - false-positive - https://youtrack.jetbrains.com/issue/WI-51002 */
        $str = (string) \preg_replace_callback(
            '~\\b (_*) (?:                                                           # 1. Leading underscore and
                        ( (?<=[ ][/\\\\]) [[:alpha:]]+ [-_[:alpha:]/\\\\]+ |                # 2. file path or 
                          [-_[:alpha:]]+ [@.:] [-_[:alpha:]@.:/]+ ' . $apostrophe_rx . ' )  #    URL, domain, or email
                        |
                        ( (?i: ' . $small_words_rx . ' ) ' . $apostrophe_rx . ' )           # 3. or small word (case-insensitive)
                        |
                        ( [[:alpha:]] [[:lower:]\'’()\[\]{}]* ' . $apostrophe_rx . ' )     # 4. or word w/o internal caps
                        |
                        ( [[:alpha:]] [[:alpha:]\'’()\[\]{}]* ' . $apostrophe_rx . ' )     # 5. or some other word
                      ) (_*) \\b                                                          # 6. With trailing underscore
                    ~ux',
            /**
             * @param string[] $matches
             *
             * @psalm-pure
             *
             * @return string
             */
            static function (array $matches) use ($encoding): string {
                // preserve leading underscore
                $str = $matches[1];
                if ($matches[2]) {
                    // preserve URLs, domains, emails and file paths
                    $str .= $matches[2];
                } elseif ($matches[3]) {
                    // lower-case small words
                    $str .= \mb_strtolower($matches[3], $encoding);
                } elseif ($matches[4]) {
                    // capitalize word w/o internal caps
                    $str .= ucfirst($matches[4]);
                } else {
                    // preserve other kinds of word (iPhone)
                    $str .= $matches[5];
                }
                // preserve trailing underscore
                $str .= $matches[6];

                return $str;
            },
            $str
        );

        // Exceptions for small words: capitalize at start of title...
        $str = (string) \preg_replace_callback(
            '~(  \\A [[:punct:]]*            # start of title...
                      |  [:.;?!][ ]+                # or of subsentence...
                      |  [ ][\'"“‘(\[][ ]* )        # or of inserted subphrase...
                      ( ' . $small_words_rx . ' ) \\b # ...followed by small word
                     ~uxi',
            /**
             * @param string[] $matches
             *
             * @psalm-pure
             */
            static function (array $matches): string {
                return $matches[1] . ucfirst($matches[2]);
            },
            $str
        );

        // ...and end of title
        $str = (string) \preg_replace_callback(
            '~\\b ( ' . $small_words_rx . ' ) # small word...
                      (?= [[:punct:]]* \Z          # ...at the end of the title...
                      |   [\'"’”)\]] [ ] )         # ...or of an inserted subphrase?
                     ~uxi',
            /**
             * @param string[] $matches
             *
             * @psalm-pure
             */
            static function (array $matches): string {
                return ucfirst($matches[1]);
            },
            $str
        );

        // Exceptions for small words in hyphenated compound words.
        // e.g. "in-flight" -> In-Flight
        $str = (string) \preg_replace_callback(
            '~\\b
                        (?<! -)                   # Negative lookbehind for a hyphen; we do not want to match man-in-the-middle but do want (in-flight)
                        ( ' . $small_words_rx . ' )
                        (?= -[[:alpha:]]+)        # lookahead for "-someword"
                       ~uxi',
            /**
             * @param string[] $matches
             *
             * @psalm-pure
             *
             * @return string
             */
            static function (array $matches) use ($encoding): string {
                return static::ucfirst($matches[1], $encoding);
            },
            $str
        );

        // e.g. "Stand-in" -> "Stand-In" (Stand is already capped at this point)
        $str = (string) \preg_replace_callback(
            '~\\b
                      (?<!…)                    # Negative lookbehind for a hyphen; we do not want to match man-in-the-middle but do want (stand-in)
                      ( [[:alpha:]]+- )         # $1 = first word and hyphen, should already be properly capped
                      ( ' . $small_words_rx . ' ) # ...followed by small word
                      (?!	- )                 # Negative lookahead for another -
                     ~uxi',
            /**
             * @param string[] $matches
             *
             * @psalm-pure
             *
             * @return string
             */
            static function (array $matches) use ($encoding): string {
                return $matches[1] . static::ucfirst($matches[2], $encoding);
            },
            $str
        );

        return $str;
    }
}
