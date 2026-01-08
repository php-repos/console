<?php

namespace PhpRepos\Console\Solution\Paths;

use PhpRepos\Console\Infra\Arrays;
use PhpRepos\Console\Infra\Filesystem;

/**
 * Checks if a directory exists.
 *
 * @param string $path Path to check
 * @return bool True if directory exists, false otherwise
 */
function exists(string $path): bool
{
    return Filesystem\exists($path);
}

/**
 * Gets all files matching a suffix in a directory recursively.
 *
 * @param string $directory Directory to search
 * @param string $suffix File suffix to match (e.g., 'Command.php')
 * @return array Sorted array of matching file paths
 */
function get_all_matching_files(string $directory, string $suffix): array
{
    $all_files = Filesystem\ls_all_recursively($directory);
    $matching_files = Arrays\filter($all_files, function (string $path) use ($suffix) {
        return is_file($path) && str_ends_with($path, $suffix);
    });

    return Arrays\sort(Arrays\to_array($matching_files));
}
