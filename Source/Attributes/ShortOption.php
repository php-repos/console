<?php

namespace PhpRepos\Console\Attributes;

use Attribute;
use InvalidArgumentException;

/**
 * ShortOption attribute for defining short command-line options.
 *
 * This attribute is used to define short command-line options (e.g., -o) for command parameters.
 * Short options must consist of a single character.
 *
 * Usage example:
 * ```
 * #[ShortOption('o')]
 * ```
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class ShortOption
{
    /**
     * Constructor for the ShortOption attribute.
     *
     * @param string $option The short option flag (a single character).
     *
     * @throws InvalidArgumentException If the provided option does not consist of a single character.
     */
    public function __construct(public readonly string $option)
    {
        if (strlen($this->option) !== 1) {
            throw new InvalidArgumentException('Short options must have one character.');
        }
    }
}
