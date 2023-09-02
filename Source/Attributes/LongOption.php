<?php

namespace PhpRepos\Console\Attributes;

use Attribute;
use InvalidArgumentException;

/**
 * LongOption attribute for defining long command-line options.
 *
 * This attribute is used to define long command-line options (e.g., --option) for command parameters.
 * Long options must consist of more than one character.
 *
 * Usage example:
 * ```
 * #[LongOption('option')]
 * ```
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class LongOption
{
    /**
     * Constructor for the LongOption attribute.
     *
     * @param string $option The long option flag (more than one character).
     *
     * @throws InvalidArgumentException If the provided option has one character or less.
     */
    public function __construct(public readonly string $option)
    {
        if (strlen($this->option) <= 1) {
            throw new InvalidArgumentException('Long options must have more than one character.');
        }
    }
}
