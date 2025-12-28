<?php

namespace PhpRepos\Console\Infra\Strings;

use AssertionError;

/**
 * Returns the substring after the first occurrence of a needle.
 *
 * @param string $subject The input string.
 * @param string $needle The substring to search for.
 * @return string The substring after the first occurrence, or the original string if needle not found.
 */
function after_first_occurrence(string $subject, string $needle): string
{
    if ($needle === '' || ($pos = mb_strpos($subject, $needle)) === false) {
        return $subject;
    }

    return mb_substr($subject, $pos + mb_strlen($needle));
}

/**
 * Joins non-null strings with a suffix.
 *
 * @param string $suffix The suffix to join with.
 * @param string|null ...$subjects The strings to join.
 * @return string The concatenated string.
 */
function concat(string $suffix, ?string ...$subjects): string
{
    return implode($suffix, array_filter($subjects));
}

/**
 * Returns the first line of a string.
 *
 * @param string $subject The input string.
 * @return string The first line, trimmed.
 */
function first_line(string $subject): string
{
    $subject = ltrim($subject, PHP_EOL);
    $pos = strpos($subject, PHP_EOL);

    if ($pos !== false) {
        return trim(substr($subject, 0, $pos));
    }

    return trim($subject);
}

/**
 * Converts a string to kebab-case format.
 *
 * @param string $subject The input string.
 * @return string The kebab-cased string.
 */
function kebab_case(string $subject): string
{
    // Convert camelCase/PascalCase to snake_case
    $subject = preg_replace('/([a-z])([A-Z])/', '$1_$2', $subject);

    // Replace spaces and underscores with hyphens
    $subject = preg_replace('/[ _]+/', '-', $subject);

    return strtolower($subject);
}

/**
 * Prepends a prefix to a string if the string exists.
 *
 * @param string|null $subject The input string.
 * @param string $prefix The prefix to prepend.
 * @return string The prefixed string, or empty string if subject is null/empty.
 */
function prepend_when_exists(?string $subject, string $prefix): string
{
    return $subject ? $prefix . $subject : '';
}

/**
 * Asserts that two values are equal when converted to strings.
 *
 * @param mixed $actual The actual value.
 * @param mixed $expected The expected value.
 * @param string|null $message Optional error message.
 * @return true Returns true if values are equal.
 * @throws AssertionError If values are not equal.
 */
function assert_equal(mixed $actual, mixed $expected, ?string $message = null): true
{
    if ((string) $actual === (string) $expected) {
        return true;
    }

    if ($message) {
        throw new AssertionError($message);
    }

    throw new AssertionError("Strings are not equal. Expected `$expected` but the actual string is `$actual`.");
}
