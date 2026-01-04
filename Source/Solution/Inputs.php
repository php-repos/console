<?php

namespace PhpRepos\Console\Solution\Inputs;

use PhpRepos\Console\Solution\Data\Input;

/**
 * Find the index position where the command name ends in the input array.
 *
 * Iterates through inputs to find the longest matching command name from available
 * handlers, returning the index after the last matched word.
 *
 * @param array $inputs User input arguments
 * @param array $command_handlers Available command handlers
 * @return int Index position after the command name (1-based)
 */
function command_index(array $inputs, array $command_handlers): int
{
    $command_index = 0;

    foreach ($inputs as $index => $input) {
        $input_string = implode(' ', array_slice($inputs, 0, $index + 1));
        $matches = array_filter($command_handlers, fn ($name) => $name === $input_string, ARRAY_FILTER_USE_KEY);
        if (count($matches) === 1) {
            $command_index = $index;
            break;
        }
    }
    return $command_index + 1;
}

/**
 * Extract the remaining inputs after the command name.
 *
 * Returns all input arguments that come after the command name, which will
 * be passed to the command handler for processing.
 *
 * @param array $inputs User input arguments
 * @param int $command_index Index position after the command name
 * @return Input Input object containing the remaining arguments
 */
function extract_remaining_inputs(array $inputs, int $command_index): Input
{

    return Input::make(array_slice($inputs, $command_index));
}
