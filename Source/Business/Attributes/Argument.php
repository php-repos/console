<?php

namespace PhpRepos\Console\Business\Attributes;

use Attribute;

/**
 * Argument attribute for indicating that a command parameter accepts an argument.
 *
 * This attribute is used to mark a command parameter as one that accepts an argument.
 * When applied to a parameter, it signifies that the parameter can receive an additional
 * value as a command-line argument.
 *
 * Usage example:
 * ```
 * #[Argument]
 * ```
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class Argument
{

}
