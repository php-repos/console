<?php

namespace PhpRepos\Console\Runner;

use Closure;
use PhpRepos\Cli\Output;
use PhpRepos\Console\Arguments;
use PhpRepos\Console\CommandParameter;
use PhpRepos\Console\Environment;
use PhpRepos\Console\Exceptions\InvalidCommandDefinitionException;
use PhpRepos\Console\Exceptions\InvalidCommandPromptException;
use PhpRepos\Console\ParamCollection;
use PhpRepos\Datatype\Map;
use PhpRepos\Datatype\Pair;
use PhpRepos\FileManager\Path;
use ReflectionException;
use function PhpRepos\Console\Reflection\docblock_to_text;
use function PhpRepos\Datatype\Arr\first;
use function PhpRepos\Datatype\Arr\max_key_length;
use function PhpRepos\Datatype\Arr\reduce;
use function PhpRepos\Datatype\Str\after_first_occurrence;
use function PhpRepos\Datatype\Str\concat;
use function PhpRepos\Datatype\Str\first_line;
use function PhpRepos\Datatype\Str\kebab_case;
use function PhpRepos\Datatype\Str\prepend_when_exists;
use function PhpRepos\FileManager\Directory\exists;
use function PhpRepos\FileManager\Directory\is_empty;
use function PhpRepos\FileManager\Directory\ls_recursively;

/**
 * Run the console application with the given configuration.
 *
 * @param Environment $environment The environment for the console application.
 *
 * @return int The exit code.
 * @throws ReflectionException
 */
function run(Environment $environment): int
{
    global $argv;

    $help_options = getopt('h', ['help'], $command_index);
    $should_show_help = isset($help_options['h']) || isset($help_options['help']);
    $command_name = $argv[$command_index] ?? null;

    // Check if the commands directory exists and is not empty.
    if (! exists($environment->config->commands_directory) || is_empty($environment->config->commands_directory)) {
        if ($should_show_help) {
            Output\line(<<<EOD
Usage: $environment->entry_point_name {$environment->config->additional_supported_options}[-h | --help]
               <command> [<options>] [<args>]
EOD);

            return 0;
        }

        Output\error("There is no command in {$environment->config->commands_directory} path!");

        return 1;
    }

    // List all available commands if no specific command is provided.
    $commands = ls_recursively($environment->config->commands_directory)
        ->vertices()
        ->filter(fn (Path $path) => is_file($path) && str_ends_with($path, $environment->config->commands_file_suffix));

    if (! $command_name) {
        Output\line(<<<EOD
Usage: $environment->entry_point_name {$environment->config->additional_supported_options}[-h | --help]
               <command> [<options>] [<args>]
EOD);
        Output\write(PHP_EOL . 'Here you can see a list of available commands:' . PHP_EOL);

        $commands = $commands->reduce(function ($commands, Path $command_path) use ($environment) {
            $commands[guess_name($environment, $command_path)] = first_line(docblock_to_text(require $command_path));
            return $commands;
        }, []);

        $max_key_length = max_key_length($commands);

        foreach ($commands as $command => $description) {
            $description
                ? Output\line('    ' . str_pad($command, $max_key_length + 4) . $description)
                : Output\line("    $command");
        }

        return 0;
    }

    $command_path = first(
        $commands->filter(fn (Path $path) => guess_name($environment, $path) === $command_name)->items()
    );

    if (is_null($command_path)) {
        Output\error("Command $command_name not found!");

        return 1;
    }

    $command = require $command_path;

    try {
        if ($should_show_help) {
            Output\line(command_help($environment, $command_name, $command));
            exit(0);
        }

        exit(execute(require $command_path, Arguments::from_argv()));
    } catch (InvalidCommandPromptException $exception) {
        Output\error('Error: ' . $exception->getMessage());
        Output\line(command_help($environment, $command_name, $command));
    } catch (InvalidCommandDefinitionException $exception) {
        Output\error('Error: ' . $exception->getMessage());
    }

    return 1;
}

/**
 * Execute a given command with the provided arguments.
 *
 * This function takes a closure representing the command and a set of arguments to be passed to the command.
 * It resolves the command parameters and their values and executes the command, returning the command's exit code.
 *
 * @param Closure $command The closure representing the command to be executed.
 * @param Arguments $arguments The arguments to be passed to the command.
 *
 * @return int|null The exit code of the executed command, or null if the command does not return an exit code.
 *
 * @throws InvalidCommandPromptException If the provided arguments are invalid or missing.
 * @throws ReflectionException
 */
function execute(Closure $command, Arguments $arguments): ?int
{
    $command_parameters = ParamCollection::from($command);

    $parameters = new Map();

    /** @var Map $parameters */
    $parameters = $command_parameters
        ->except(fn (CommandParameter $command_parameter) => $command_parameter->wants_excessive_arguments)
        ->reduce(
            function (Map $parameters, CommandParameter $command_parameter) use ($arguments) {
                return $command_parameter->is_option
                    ? $parameters->put(new Pair($command_parameter->name, $arguments->take_option($command_parameter)))
                    : $parameters->put(new Pair($command_parameter->name, null));
            },
            $parameters
        );

    /** @var Map $parameters */
    $parameters = $command_parameters
        ->except(fn (CommandParameter $command_parameter) => $command_parameter->wants_excessive_arguments)
        ->reduce(function (map $parameters, CommandParameter $command_parameter) use ($arguments) {
            $parameter = $parameters->first(fn (Pair $parameter) => $parameter->key === $command_parameter->name);
            $value = $parameter->value;

            if ($value === null) {
                if ($command_parameter->accepts_argument) {
                    $value = $arguments->take_argument($command_parameter);
                }

                $value = is_null($value) ? $command_parameter->default_value : $value;
            }

            if ($value === null && $command_parameter->type !== 'bool' && ! $command_parameter->is_optional) {
                if ($command_parameter->accepts_argument) {
                    $message = "Argument `$command_parameter->name` is required.";
                } else {
                    $hint = concat('|', $command_parameter->short_option, $command_parameter->long_option);
                    $message = "Option `$hint` is required.";
                }

                throw new InvalidCommandPromptException($message);
            }

            $parameters->push($parameter->value($value));

            return $parameters;
        }, $parameters);

    if ($command_parameters->has(fn (CommandParameter $parameter) => $parameter->wants_excessive_arguments)) {
        $excessive_arguments_parameter = $command_parameters->first(fn (CommandParameter $parameter) => $parameter->wants_excessive_arguments);
        $parameters->put(new Pair($excessive_arguments_parameter->name, $arguments->take_all()));
    } else if (! $arguments->all_used()) {
        throw new InvalidCommandPromptException('You passed invalid argument to the command.');
    }

    $command_parameters = $parameters->reduce(function (array $command_parameters, Pair $pair) {
        $command_parameters[$pair->key] = $pair->value;

        return $command_parameters;
    }, []);

    return call_user_func_array($command, $command_parameters);
}

/**
 * Generate a help message for a given command.
 *
 * This function takes the name of a command and its associated closure, and generates a help message
 * describing the command's usage, description, arguments, and options.
 *
 * @param Environment $environment The console environment
 * @param string $name The name of the command.
 * @param Closure $command The closure representing the command.
 *
 * @return string The generated help message for the command.
 * @throws ReflectionException
 */
function command_help(Environment $environment, string $name, Closure $command): string
{
    $parameters = ParamCollection::from($command);

    $arguments = $parameters->filter(fn (CommandParameter $command_parameter) => $command_parameter->accepts_argument);
    $options = $parameters->filter(fn (CommandParameter $command_parameter) => $command_parameter->is_option);

    $arguments = $arguments->reduce(function (array $arguments, CommandParameter $command_parameter) {
        $key = ($command_parameter->is_optional || $command_parameter->is_option) ? "[<$command_parameter->name>]" : "<$command_parameter->name>";
        $arguments[$key] = $command_parameter->description;

        return $arguments;
    }, []);

    $options = $options->reduce(function (array $options, CommandParameter $command_parameter) {
            $short_option = prepend_when_exists($command_parameter->short_option, '-');
            $long_option = prepend_when_exists($command_parameter->long_option, '--');

            $key = concat(', ', $short_option, $long_option);

            if ($command_parameter->type !== 'bool') {
                $key .= $command_parameter->is_optional ? " [<$command_parameter->name>]" : " <$command_parameter->name>";
            }

            $options[$key] = $command_parameter->description;

            return $options;
        },
        []
    );

    $arguments_short_doc = reduce($arguments, function ($arguments_doc, $description, $argument) {
        return $arguments_doc . ' ' . $argument;
    }, '');

    if (empty($arguments)) {
        $arguments_doc = PHP_EOL . 'This command does not accept any arguments.';
    } else {
        $arguments_max_key_length = max_key_length($arguments);
        $arguments_doc = reduce($arguments, function ($arguments_doc, $description, $argument) use ($arguments_max_key_length) {
            return $arguments_doc . PHP_EOL . '  ' . str_pad($argument, $arguments_max_key_length) . ($description ? ' ' . $description : '');
        }, '');
    }

    if (empty($options)) {
        $options_short_doc = '';
        $options_doc = PHP_EOL . 'This command does not accept any options.';
    } else {
        $options_short_doc = ' [<options>]';
        $options_max_key_length = max_key_length($options);
        $options_doc = reduce($options, function ($options_doc, $description, $option) use ($options_max_key_length) {
            return $options_doc . PHP_EOL . '  ' . str_pad($option, $options_max_key_length) . ' ' . $description;
        }, '');
    }

    $description = docblock_to_text($command);
    $description = $description ?: 'No description provided for the command.';

    return <<<EOD
Usage: $environment->entry_point_name $name$options_short_doc$arguments_short_doc

Description:
$description

Arguments:$arguments_doc

Options:$options_doc
EOD;
}

/**
 * Guess the name of a command based on its file path and configuration.
 *
 * This function calculates the name of a command by analyzing its file path and applying
 * the configuration settings for command file suffixes and directory structure.
 *
 * @param Environment $environment The console environment
 * @param Path $command_path The full file path to the command.
 * @return string The guessed name of the command.
 */
function guess_name(Environment $environment, Path $command_path): string
{
    $relative_part = after_first_occurrence($command_path, $environment->config->commands_directory->string() . DIRECTORY_SEPARATOR);
    $relative_part = str_replace($environment->config->commands_file_suffix, '', $relative_part);
    $parts = explode(DIRECTORY_SEPARATOR, $relative_part);

    $name = '';
    foreach ($parts as $index => $part) {
        $name .= ($index > 0 ? DIRECTORY_SEPARATOR : '') . kebab_case($part);
    }

    return $name;
}
