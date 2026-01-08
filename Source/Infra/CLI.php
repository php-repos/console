<?php

namespace PhpRepos\Console\Infra\CLI;

/**
 * Write a message to the console.
 *
 * @param string $message The message to be displayed.
 * @return bool
 */
function write(string $message): bool
{
    echo $message;
    return true;
}

/**
 * Output a line of text to the console with default text color.
 *
 * @param string $string The text to be displayed.
 * @return bool
 */
function line(string $string): bool
{
    return write("\e[39m$string" . PHP_EOL);
}

/**
 * Output an error message to the console with red text color.
 *
 * @param string $string The error message to be displayed.
 * @return bool
 */
function error(string $string): bool
{
    return write("\e[91m$string\e[39m" . PHP_EOL);
}

/**
 * Check if the current OS is Windows.
 *
 * @return bool True if running on Windows, false otherwise.
 */
function is_windows(): bool
{
    return DIRECTORY_SEPARATOR === '\\' || PHP_OS_FAMILY === 'Windows';
}

/**
 * Strip ANSI color codes from a string.
 *
 * @param string $string The string with ANSI codes.
 * @return string The string without ANSI codes.
 */
function strip_colors(string $string): string
{
    return preg_replace('/\e\[[0-9;]*m/', '', $string);
}

/**
 * Assert that the output line matches the expected value with default text color.
 *
 * @param string $expected The expected output line.
 * @param mixed $actual The actual output value.
 * @return bool Whether the assertion passed (true) or failed (false).
 */
function assert_line(string $expected, mixed $actual): bool
{
    $actual_string = (string) $actual;

    // On Windows, strip colors before comparing (ANSI support may be unreliable)
    if (is_windows()) {
        return strip_colors($actual_string) === $expected . PHP_EOL;
    }

    // On Unix/Linux/Mac, expect exact match with colors
    return $actual_string === "\e[39m$expected" . PHP_EOL;
}

/**
 * Assert that the error message matches the expected value with red text color.
 *
 * @param string $expected The expected error message.
 * @param mixed $actual The actual output value.
 * @return bool Whether the assertion passed (true) or failed (false).
 */
function assert_error(string $expected, mixed $actual): bool
{
    $actual_string = (string) $actual;

    // On Windows, strip colors before comparing (ANSI support may be unreliable)
    if (is_windows()) {
        return strip_colors($actual_string) === $expected . PHP_EOL;
    }

    // On Unix/Linux/Mac, expect exact match with colors
    return $actual_string === "\e[91m$expected\e[39m" . PHP_EOL;
}
