<?php

namespace PhpRepos\Console\Solution\Handlers;

use PhpRepos\Console\Solution\Data\CommandParameter;
use PhpRepos\Console\Solution\Inputs;
use PhpRepos\Console\Infra\Arrays;
use PhpRepos\Console\Infra\Reflections;
use PhpRepos\Console\Infra\Strings;
use ReflectionException;
use ReflectionParameter;

/**
 * Extract the command name from user inputs.
 *
 * Determines which command the user is trying to execute by finding the longest
 * matching command name from the available handlers.
 *
 * @param array $inputs User input arguments from command line
 * @param array $command_handlers Available command handlers
 * @return string The matched command name (empty string if no command found)
 */
function command(array $inputs, array $command_handlers): string
{
    $command_index = Inputs\command_index($inputs, $command_handlers);
    return implode(' ', array_slice($inputs, 0, $command_index));
}

/**
 * Find the best matching command handler for the given input.
 *
 * Searches through available commands to find the best match based on prefix matching
 * and word count. Returns the command with the highest score (most matched words and
 * shortest total length).
 *
 * @param string $input_command The command string from user input
 * @param array $command_handlers Available command handlers
 * @return array Array with 'key' (command name) and 'value' (handler), or empty if no match
 */
function match_best_command_from_inputs(string $input_command, array $command_handlers): array
{
    $best_score = [-1, -1];
    $best_match = [];

    foreach ($command_handlers as $command_name => $command_handler) {
        if (!str_starts_with($command_name, $input_command)) {
            continue;
        }

        $command_words = explode(' ', $command_name);
        $input_words = explode(' ', $input_command);

        $matched = 0;
        for ($i = 0; $i < min(count($input_words), count($command_words)); $i++) {
            if ($input_words[$i] === $command_words[$i]) {
                $matched++;
            } else {
                break;
            }
        }

        if ($matched === 0) {
            continue;
        }

        $total_chars = strlen(implode('', $command_words));
        $score = [$matched, $total_chars];

        if ($score > $best_score) {
            $best_score = $score;
            $best_match = ['key' => $command_name, 'value' => $command_handler];
        }
    }

    return $best_match;
}

/**
 * Guesses the command name from the file path.
 *
 * Extracts the relative path from root, removes the suffix, and converts to kebab-case.
 *
 * @param string $root Root directory path
 * @param string $command_file Full path to the command file
 * @param string $commands_file_suffix File suffix to remove
 * @return string The guessed command name
 */
function guess_command_name(string $root, string $command_file, string $commands_file_suffix): string
{
    $relative_part = Strings\after_first_occurrence($command_file, $root . DIRECTORY_SEPARATOR);
    $relative_part = str_replace($commands_file_suffix, '', $relative_part);
    $parts = explode(DIRECTORY_SEPARATOR, $relative_part);

    $name = '';
    foreach ($parts as $index => $part) {
        $name .= ($index > 0 ? ' ' : '') . Strings\kebab_case($part);
    }

    return $name;
}

/**
 * Extract the short description from a command handler.
 *
 * Returns only the first line of the command's docblock, suitable for
 * use in command listings.
 *
 * @param callable $command The command handler
 * @return string The first line of the docblock, or empty string if none exists
 */
function description(callable $command): string
{
    $docblock = Reflections\docblock_to_text($command);
    return Strings\first_line($docblock);
}

/**
 * Get the full description of a command handler.
 *
 * Extracts the complete docblock text from the command handler. Returns a
 * default message if no docblock exists.
 *
 * @param callable $command The command handler
 * @return string The full docblock text, or default message if none exists
 * @throws ReflectionException If reflection fails
 */
function get_description(callable $command): string
{
    $docblock = Reflections\docblock_to_text($command);
    return $docblock ?: 'No description provided for the command.';
}

/**
 * Extract all argument definitions from a command handler.
 *
 * Analyzes the command handler's parameters and returns detailed information
 * about each argument, including name, whether it's required, and its description.
 *
 * @param callable $command The command handler
 * @return array Array of arguments, each with 'name', 'required', and 'description' keys
 * @throws ReflectionException If reflection fails
 */
function get_arguments(callable $command): array
{
    $command_parameters = Arrays\map(
        Reflections\function_parameters($command),
        fn (ReflectionParameter $param) => CommandParameter::create($param)
    );

    return Arrays\map(
        Arrays\filter($command_parameters, fn (CommandParameter $p) => $p->accepts_argument),
        function (CommandParameter $p) {
            return [
                'name' => $p->name,
                'required' => !$p->is_optional && !$p->is_option,
                'description' => $p->description,
            ];
        }
    );
}

/**
 * Extract all option definitions from a command handler.
 *
 * Analyzes the command handler's parameters and returns detailed information
 * about each option, including short/long flags, type, requirement status, and description.
 *
 * @param callable $command The command handler
 * @return array Array of options, each with 'short', 'long', 'name', 'type', 'required', and 'description' keys
 * @throws ReflectionException If reflection fails
 */
function get_options(callable $command): array
{
    $command_parameters = Arrays\map(
        Reflections\function_parameters($command),
        fn (ReflectionParameter $param) => CommandParameter::create($param)
    );

    return Arrays\map(
        Arrays\filter($command_parameters, fn (CommandParameter $p) => $p->is_option),
        function (CommandParameter $p) {
            return [
                'short' => $p->short_option,
                'long' => $p->long_option,
                'name' => $p->name,
                'type' => $p->type,
                'required' => !$p->is_optional,
                'description' => $p->description,
            ];
        }
    );
}
