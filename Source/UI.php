<?php

namespace PhpRepos\Console\UI;

use PhpRepos\Console\Infra\Arrays;

/**
 * Format a short help message listing all available commands.
 *
 * Generates a formatted help text showing the usage syntax and a list of all
 * available commands with their short descriptions. Commands without descriptions
 * are listed without trailing spaces.
 *
 * @param array $commands Array of commands, each with 'name' and optional 'description'
 * @param string $entrypoint The name of the console entry point (e.g., 'console')
 * @return string Formatted help text for terminal display
 */
function short_help(array $commands, string $entrypoint): string
{
    $output = "Usage: $entrypoint [-h | --help]\n";
    $output .= "               <command> [<options>] [<args>]\n";

    if (empty($commands)) {
        return $output;
    }

    $output .= "\n";
    $output .= "Here you can see a list of available commands:\n";

    $max_length = max(Arrays\map($commands, fn($cmd) => strlen($cmd['name'])));

    foreach ($commands as $command) {
        $output .= "    ";

        if (!empty($command['description'])) {
            $output .= str_pad($command['name'], $max_length + 4);
            $output .= $command['description'];
        } else {
            $output .= $command['name'];
        }

        $output .= "\n";
    }

    return $output;
}

/**
 * Format detailed help information for a specific command.
 *
 * Generates a comprehensive help text showing the usage syntax, description,
 * arguments, and options for a command. Formats optional arguments and options
 * with proper bracketing notation (e.g., [<name>]).
 *
 * @param array $command_data Command details with 'description', 'arguments', and 'options' keys
 * @param string $entrypoint The name of the console entry point (e.g., 'console')
 * @param string $command_name The name of the command being described
 * @return string Formatted detailed help text for terminal display
 */
function long_help(array $command_data, string $entrypoint, string $command_name): string
{
    $output = "Usage: $entrypoint $command_name";

    // Add options placeholder if command has options
    if (!empty($command_data['options'])) {
        $output .= " [<options>]";
    }

    // Add arguments
    foreach ($command_data['arguments'] as $arg) {
        if ($arg['required']) {
            $output .= " <{$arg['name']}>";
        } else {
            $output .= " [<{$arg['name']}>]";
        }
    }

    $output .= "\n\n";
    $output .= "Description:\n";
    $output .= $command_data['description'] . "\n\n";
    $output .= "Arguments:\n";

    if (empty($command_data['arguments'])) {
        $output .= "This command does not accept any arguments.\n";
    } else {
        $arg_pairs = Arrays\map($command_data['arguments'], function($arg) {
            $key = $arg['required'] ? "<{$arg['name']}>" : "[<{$arg['name']}>]";
            return ['key' => $key, 'value' => $arg['description']];
        });

        $max_length = max(Arrays\map($arg_pairs, fn($p) => strlen($p['key'])));

        foreach ($arg_pairs as $pair) {
            $output .= "  " . str_pad($pair['key'], $max_length);
            if (!empty($pair['value'])) {
                $output .= " " . $pair['value'];
            }
            $output .= "\n";
        }
    }

    $output .= "\n";
    $output .= "Options:\n";

    if (empty($command_data['options'])) {
        $output .= "This command does not accept any options.\n";
    } else {
        $option_pairs = Arrays\map($command_data['options'], function($opt) {
            $parts = [];
            if (!empty($opt['short'])) {
                $parts[] = "-{$opt['short']}";
            }
            if (!empty($opt['long'])) {
                $parts[] = "--{$opt['long']}";
            }

            $key = implode(', ', $parts);

            if ($opt['type'] !== 'bool') {
                if ($opt['required']) {
                    $key .= " <{$opt['name']}>";
                } else {
                    $key .= " [<{$opt['name']}>]";
                }
            }

            return ['key' => $key, 'value' => $opt['description']];
        });

        $max_length = max(Arrays\map($option_pairs, fn($p) => strlen($p['key'])));

        foreach ($option_pairs as $pair) {
            $output .= "  " . str_pad($pair['key'], $max_length);
            if (!empty($pair['value'])) {
                $output .= " " . $pair['value'];
            }
            $output .= "\n";
        }
    }

    return $output;
}
