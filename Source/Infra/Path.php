<?php

namespace PhpRepos\Console\Infra;

use function PhpRepos\Console\Infra\Filesystem\realpath;

/**
 * A class for handling file system paths.
 */
class Path
{
    public function __construct(
        protected string $path
    ) {
    }

    /**
     * Creates a new Path instance from a string.
     *
     * @param string $path_string The path string.
     * @return static A new Path instance.
     */
    public static function from(string $path_string): static
    {
        return new static($path_string);
    }

    /**
     * Creates a new Path instance from a string, normalizing it to an absolute path.
     *
     * @param string $path_string The path string to normalize.
     * @return static A new Path instance with the normalized path.
     */
    public static function from_string(string $path_string): static
    {
        return new static(realpath($path_string));
    }

    /**
     * Appends one or more path segments to the current path.
     *
     * @param string ...$segments Path segments to append.
     * @return static The current Path instance with the updated path.
     */
    public function sub(string ...$segments): static
    {
        foreach ($segments as $segment) {
            $this->path .= DIRECTORY_SEPARATOR . $segment;
        }

        return $this;
    }

    /**
     * Gets the string representation of the path.
     *
     * @return string The path as a string.
     */
    public function __toString(): string
    {
        return $this->path;
    }
}
