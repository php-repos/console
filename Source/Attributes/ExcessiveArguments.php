<?php

namespace PhpRepos\Console\Attributes;

use Attribute;

/**
 * Excessive Arguments attribute for passing remaining arguments as an array to the command.
 *
 * This attribute is used to define a variable as the variable to pass any excessive arguments
 * passed to the command.
 * Note: The correspond variable must always be an array.
 *
 * Usage example:
 * ```
 * #[ExcessiveArguments]
 * ```
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class ExcessiveArguments
{

}
