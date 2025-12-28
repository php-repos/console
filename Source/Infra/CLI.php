<?php

namespace PhpRepos\Console\Infra\CLI;

/**
 * Write a message to the console.
 *
 * @param string $message The message to be displayed.
 * @return void
 */
function write(string $message): void
{
    echo $message;
}

/**
 * Output a line of text to the console with default text color.
 *
 * @param string $string The text to be displayed.
 * @return void
 */
function line(string $string): void
{
    write("\e[39m$string" . PHP_EOL);
}

/**
 * Output an error message to the console with red text color.
 *
 * @param string $string The error message to be displayed.
 * @return void
 */
function error(string $string): void
{
    write("\e[91m$string\e[39m" . PHP_EOL);
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
    return (string) $actual === "\e[39m$expected" . PHP_EOL;
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
    return (string) $actual === "\e[91m$expected\e[39m" . PHP_EOL;
}
