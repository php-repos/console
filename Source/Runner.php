<?php

namespace PhpRepos\Console\Runner;

use Closure;
use PhpRepos\Cli\Output;
use PhpRepos\Console\Arguments;
use PhpRepos\Console\CommandParameter;
use PhpRepos\Console\Environment;
use PhpRepos\Console\Exceptions\InvalidCommandDefinitionException;
use PhpRepos\Console\Exceptions\InvalidCommandPromptException;
use PhpRepos\Console\Signals\CommandExecutionCompleted;
use PhpRepos\Console\Signals\RunningConsoleCommand;
use PhpRepos\Console\Signals\ConsoleSessionStarted;
use PhpRepos\Datatype\Map;
use PhpRepos\FileManager\Path;
use PhpRepos\Observer\Observer;
use ReflectionException;
use ReflectionParameter;
use function PhpRepos\Console\Reflection\docblock_to_text;
use function PhpRepos\Console\Reflection\function_parameters;
use function PhpRepos\Datatype\Arr\any;
use function PhpRepos\Datatype\Arr\filter;
use function PhpRepos\Datatype\Arr\first;
use function PhpRepos\Datatype\Arr\map;
use function PhpRepos\Datatype\Arr\max_key_length;
use function PhpRepos\Datatype\Arr\reduce;
use function PhpRepos\Datatype\Str\after_first_occurrence;
use function PhpRepos\Datatype\Str\concat;
use function PhpRepos\Datatype\Str\first_line;
use function PhpRepos\Datatype\Str\kebab_case;
use function PhpRepos\Datatype\Str\prepend_when_exists;
use function PhpRepos\FileManager\Directories\exists;
use function PhpRepos\FileManager\Directories\is_empty;
use function PhpRepos\FileManager\Directories\ls_all;

/**
 * Run the console application with the given configuration.
 *
 * @param Environment $environment The environment for the console application.
 * @param array $argv The passed arguments from argv
 *
 * @return int The exit code.
 * @throws ReflectionException
 */
function run(Environment $environment, array $argv): int
{
    Observer\send(ConsoleSessionStarted::by($argv));

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

    $commands = filter(ls_all($environment->config->commands_directory), fn ($path) => is_file($path) && str_ends_with($path, $environment->config->commands_file_suffix));

    $commands = map($commands, fn ($path_string) => Path::from($path_string));

    if (! $command_name) {
        Output\line(<<<EOD
Usage: $environment->entry_point_name {$environment->config->additional_supported_options}[-h | --help]
               <command> [<options>] [<args>]
EOD);
        Output\write(PHP_EOL . 'Here you can see a list of available commands:' . PHP_EOL);

        $commands = reduce($commands, function ($commands, Path $command_path) use ($environment) {
            $commands[guess_name($environment, $command_path)] = first_line(docblock_to_text(require $command_path));
            return $commands;
        }, []);

        ksort($commands);

        $max_key_length = max_key_length($commands);

        foreach ($commands as $command => $description) {
            $description
                ? Output\line('    ' . str_pad($command, $max_key_length + 4) . $description)
                : Output\line("    $command");
        }

        return 0;
    }

    $command_path = first($commands, fn (Path $path) => guess_name($environment, $path) === $command_name);

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

        Observer\send(RunningConsoleCommand::from_path($command_path));
        $return = execute(require $command_path, Arguments::from_argv());
        $return = $return !== null ? $return : 0;
        Observer\send(CommandExecutionCompleted::successfully($command_path, $return));
        exit($return);
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
    $parameters = function_parameters($command);
    $command_parameters = new Map(map($parameters, fn (ReflectionParameter $param) => [CommandParameter::create($param), null]));

    $command_parameters->map(function (mixed $value, CommandParameter $command_parameter) use ($arguments) {
        return $command_parameter->is_option && ! $command_parameter->wants_excessive_arguments ? $arguments->take_option($command_parameter) : $value;
    });

    $command_parameters->map(function (mixed $value, CommandParameter $command_parameter) use ($arguments) {
        if ($command_parameter->wants_excessive_arguments) {
            return $value;
        }

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

        return $value;
    });

    if (any($command_parameters, fn (array $pair) => $pair['key']->wants_excessive_arguments)) {
        $excessive_values = $arguments->take_all();
        $command_parameters->map(function (mixed $value, CommandParameter $command_parameter) use ($excessive_values) {
           return $command_parameter->wants_excessive_arguments ? $excessive_values : $value;
        });
    }  else if (! $arguments->all_used()) {
        throw new InvalidCommandPromptException('You passed invalid argument to the command.');
    }

    $command_parameters = Map::from(map($command_parameters, fn (array $pair) => ['key' => $pair['key']->name, 'value' => $pair['value']]));

    return call_user_func_array($command, array_column($command_parameters->to_array(), 'value', 'key'));
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
    $command_parameters = map(function_parameters($command), fn (ReflectionParameter $param) => CommandParameter::create($param));

    $arguments = Map::from(map(filter($command_parameters, fn (CommandParameter $command_parameter) => $command_parameter->accepts_argument), function (CommandParameter $command_parameter) {
        $key = ($command_parameter->is_optional || $command_parameter->is_option) ? "[<$command_parameter->name>]" : "<$command_parameter->name>";

        return ['key' => $key, 'value' => $command_parameter->description];
    }));

    $options = Map::from(map(filter($command_parameters, fn (CommandParameter $command_parameter) => $command_parameter->is_option), function (CommandParameter $command_parameter) {
        $short_option = prepend_when_exists($command_parameter->short_option, '-');
        $long_option = prepend_when_exists($command_parameter->long_option, '--');

        $key = concat(', ', $short_option, $long_option);

        if ($command_parameter->type !== 'bool') {
            $key .= $command_parameter->is_optional ? " [<$command_parameter->name>]" : " <$command_parameter->name>";
        }

        return ['key' => $key, 'value' => $command_parameter->description];
    }));

    $arguments_short_doc = reduce($arguments, function ($arguments_doc, array $pair) {
        return $arguments_doc . ' ' . $pair['key'];
    }, '');

    if ($arguments->count() === 0) {
        $arguments_doc = PHP_EOL . 'This command does not accept any arguments.';
    } else {
        $arguments_max_key_length = max(map($arguments, fn (array $pair) => strlen($pair['key'])));
        $arguments_doc = reduce($arguments, function ($arguments_doc, $pair) use ($arguments_max_key_length) {
            return $arguments_doc . PHP_EOL . '  ' . str_pad($pair['key'], $arguments_max_key_length) . ($pair['value'] ? ' ' . $pair['value'] : '');
        }, '');
    }

    if ($options->count() === 0) {
        $options_short_doc = '';
        $options_doc = PHP_EOL . 'This command does not accept any options.';
    } else {
        $options_short_doc = ' [<options>]';
        $options_max_key_length = max(map($options, fn (array $pair) => strlen($pair['key'])));
        $options_doc = reduce($options, function ($options_doc, array $pair) use ($options_max_key_length) {
            return $options_doc . PHP_EOL . '  ' . str_pad($pair['key'], $options_max_key_length) . ' ' . $pair['value'];
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
