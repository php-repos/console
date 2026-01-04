<?php

namespace PhpRepos\Console\Infra\Arrays;

/**
 * Converts an iterable to an array.
 *
 * @param iterable $iterable The input iterable.
 * @return array The converted array.
 */
function to_array(iterable $iterable): array
{
    return is_array($iterable) ? $iterable : iterator_to_array($iterable);
}

/**
 * Checks if any element in the iterable satisfies a condition.
 *
 * @param iterable $array The input iterable.
 * @param callable|null $condition Callback to test each element. Receives value and key as parameters.
 * @return bool True if any element satisfies the condition, false otherwise.
 */
function any(iterable $array, ?callable $condition = null): bool
{
    if (is_callable($condition)) {
        foreach ($array as $key => $value) {
            if ($condition($value, $key)) {
                return true;
            }
        }
        return false;
    }

    $array = to_array($array);
    return !empty($array);
}

/**
 * Filters iterable elements using a callback function.
 *
 * @param iterable $array The input iterable.
 * @param callable|null $callback Callback to filter elements. Receives value and key as parameters.
 * @return array The filtered array.
 */
function filter(iterable $array, ?callable $callback = null): array
{
    $array = to_array($array);
    return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
}

/**
 * Applies a callback to each element in the iterable.
 *
 * @param iterable $array The input iterable.
 * @param callable $callback Callback to apply. Receives value and key as parameters.
 * @return array The mapped array.
 */
function map(iterable $array, callable $callback): array
{
    $array = to_array($array);
    return array_map($callback, array_values($array), array_keys($array));
}

/**
 * Returns the maximum length of keys in the iterable.
 *
 * @param iterable $array The input iterable.
 * @return int The maximum key length, or 0 if empty.
 */
function max_key_length(iterable $array): int
{
    $array = to_array($array);
    return empty($array) ? 0 : max(array_map('mb_strlen', array_keys($array)));
}

/**
 * Reduces the iterable to a single value using a callback function.
 *
 * @param iterable $array The input iterable.
 * @param callable $callback Callback to reduce. Receives carry, value, and key as parameters.
 * @param mixed $carry The initial value.
 * @return mixed The reduced value.
 */
function reduce(iterable $array, callable $callback, mixed $carry = null): mixed
{
    foreach ($array as $key => $value) {
        $carry = $callback($carry, $value, $key);
    }
    return $carry;
}

/**
 * Returns the first element that satisfies a condition.
 *
 * @param iterable $array The input iterable.
 * @param callable|null $condition Callback to test each element. Receives value and key as parameters.
 * @return mixed The first matching value, or null if none found.
 */
function first(iterable $array, ?callable $condition = null): mixed
{
    if (is_callable($condition)) {
        foreach ($array as $key => $value) {
            if ($condition($value, $key)) {
                return $value;
            }
        }
        return null;
    }

    $array = to_array($array);
    return !empty($array) ? reset($array) : null;
}

/**
 * Returns the key of the first element that satisfies a condition.
 *
 * @param iterable $array The input iterable.
 * @param callable|null $condition Callback to test each element. Receives value and key as parameters.
 * @return string|int|null The first matching key, or null if none found.
 */
function first_key(iterable $array, ?callable $condition = null): string|int|null
{
    if (is_callable($condition)) {
        foreach ($array as $key => $value) {
            if ($condition($value, $key)) {
                return $key;
            }
        }
        return null;
    }

    $array = to_array($array);
    return !empty($array) ? array_key_first($array) : null;
}

/**
 * Returns the last element that satisfies a condition.
 *
 * @param iterable $array The input iterable.
 * @param callable|null $condition Callback to test each element. Receives value and key as parameters.
 * @return mixed The last matching value, or null if none found.
 */
function last(iterable $array, ?callable $condition = null): mixed
{
    if (is_callable($condition)) {
        $last = null;
        foreach ($array as $key => $value) {
            $last = $condition($value, $key) ? $value : $last;
        }
        return $last;
    }

    $array = to_array($array);
    return !empty($array) ? end($array) : null;
}

/**
 * Sorts an array and returns it.
 *
 * @param array $array The array to sort.
 * @return array The sorted array.
 */
function sort(array $array): array
{
    \sort($array);
    return $array;
}
