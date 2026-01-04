<?php

namespace PhpRepos\Console\Infra\Filesystem;

use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

/**
 * Retrieves the current working directory with a trailing directory separator.
 *
 * @return string The absolute path to the current working directory.
 */
function root(): string
{
    return getcwd() . DIRECTORY_SEPARATOR;
}

/**
 * Normalizes and resolves a path to its absolute form using PHP's native realpath.
 *
 * @param string $path_string The path to normalize.
 * @return string The resolved absolute path, or the original path if it doesn't exist.
 */
function realpath(string $path_string): string
{
    $resolved = \realpath($path_string);
    return $resolved !== false ? $resolved : $path_string;
}

/**
 * Checks if a directory exists.
 *
 * @param string $path The path to check.
 * @return bool True if the path is a directory, false otherwise.
 */
function exists(string $path): bool
{
    return file_exists($path) && is_dir($path);
}

/**
 * Lists all directory contents recursively with an optional filter.
 *
 * @param string $directory The path to the directory.
 * @param callable|null $filter An optional callback to filter paths.
 * @param int|null $mode The iteration mode.
 * @return RecursiveIteratorIterator An iterator over the directory contents.
 */
function ls_all_recursively(string $directory, ?callable $filter = null, ?int $mode = null): RecursiveIteratorIterator
{
    $mode = $mode ?: RecursiveIteratorIterator::SELF_FIRST;
    $iterator = new RecursiveDirectoryIterator(
        $directory,
        FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME
    );

    $iterator = is_callable($filter) ? new RecursiveCallbackFilterIterator($iterator, $filter) : $iterator;

    return new RecursiveIteratorIterator($iterator, $mode);
}

/**
 * Deletes a directory and all its contents recursively.
 *
 * @param string $path The path to the directory.
 * @return bool True on success, false on failure.
 */
function delete_recursive(string $path): bool
{
    // List all contents in reverse order (children first)
    $iterator = ls_all_recursively($path, null, RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($iterator as $item) {
        if (is_dir($item)) {
            rmdir($item);
        } else {
            unlink($item);
        }
    }

    return rmdir($path);
}

/**
 * Recursively copies a directory and its contents, preserving permissions.
 *
 * @param string $origin The source directory path.
 * @param string $destination The destination directory path.
 * @return bool True on success, false on failure.
 */
function preserve_copy_recursively(string $origin, string $destination): bool
{
    // Get permission from origin directory
    clearstatcache();
    $origin_permission = fileperms($origin) & 0x0FFF;

    // Create destination directory with same permissions
    $old_umask = umask(0);
    $created = mkdir($destination, $origin_permission);
    umask($old_umask);

    if (!$created) {
        return false;
    }

    foreach (ls_all_recursively($origin) as $item) {
        $relative = substr($item, strlen($origin) + 1);
        $destPath = $destination . DIRECTORY_SEPARATOR . $relative;

        if (is_dir($item)) {
            $item_permission = fileperms($item) & 0x0FFF;
            $old_umask = umask(0);
            $created = mkdir($destPath, $item_permission);
            umask($old_umask);
        } elseif (is_link($item)) {
            $target = readlink($item);
            $created = symlink($target, $destPath);
        } else {
            $created = copy($item, $destPath);
            if ($created) {
                chmod($destPath, fileperms($item) & 0x0FFF);
            }
        }

        if (!$created) {
            break;
        }
    }

    return $created;
}
