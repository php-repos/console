<?php

namespace PhpRepos\Console\Attributes;

use Attribute;

/**
 * Description attribute for providing descriptions to command parameters.
 *
 * This attribute is used to provide human-readable descriptions for command parameters.
 * Descriptions help users understand the purpose and usage of individual parameters.
 *
 * Usage example:
 * ```
 * #[Description('Specifies the input file for processing.')]
 * ```
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class Description
{
    /**
     * Constructor for the Description attribute.
     *
     * @param string $description The description text for the command parameter.
     */
    public function __construct(public readonly string $description) {}
}
