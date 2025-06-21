<?php

namespace PhpRepos\Console\Runner;

use PhpRepos\Cli\Output;
use PhpRepos\Console\CommandHandlers;
use PhpRepos\Console\CommandParameter;
use PhpRepos\Console\Exceptions\InvalidCommandDefinitionException;
use PhpRepos\Console\Exceptions\InvalidCommandPromptException;
use PhpRepos\Console\Input;
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
use function PhpRepos\Datatype\Arr\map;
use function PhpRepos\Datatype\Arr\max_key_length;
use function PhpRepos\Datatype\Arr\reduce;
use function PhpRepos\Datatype\Str\after_first_occurrence;
use function PhpRepos\Datatype\Str\concat;
use function PhpRepos\Datatype\Str\first_line;
use function PhpRepos\Datatype\Str\kebab_case;
use function PhpRepos\Datatype\Str\prepend_when_exists;
use function PhpRepos\FileManager\Directories\exists;
use function PhpRepos\FileManager\Directories\ls_all;

/**
 * Loads commands from the given commands path
 *
 * @param Path $root
 * @param string $commands_file_suffix
 * @return CommandHandlers
 */
function from_path(Path $root, string $commands_file_suffix = 'Command.php'): CommandHandlers
{
    $command_handlers = new CommandHandlers();

    if (!exists($root)) {
        return $command_handlers;
    }

    $files = filter(ls_all($root), fn (string $path) => is_file($path) && str_ends_with($path, $commands_file_suffix));

    sort($files);

    return reduce($files, function (CommandHandlers $command_handlers, string $path) use ($root, $commands_file_suffix) {
        $handler = require $path;
        $relative_part = after_first_occurrence($path, $root . DIRECTORY_SEPARATOR);
        $relative_part = str_replace($commands_file_suffix, '', $relative_part);
        $parts = explode(DIRECTORY_SEPARATOR, $relative_part);

        $name = '';
        foreach ($parts as $index => $part) {
            $name .= ($index > 0 ? ' ' : '') . kebab_case($part);
        }
        return $command_handlers->add($name, $handler);
    }, $command_handlers);
}

/**
 * Run the console application with the given input command and arguments.
 *
 * @param CommandHandlers $command_handlers
 * @param Input $inputs
 * @param string $entrypoint
 * @param string $help_text
 * @param bool $wants_help
 * @param Path $commands_directory
 * @return int
 * @throws ReflectionException
 */
function run(CommandHandlers $command_handlers, Input $inputs, string $entrypoint, string $help_text, bool $wants_help, Path $commands_directory): int
{
    Observer\send(ConsoleSessionStarted::by($inputs->to_array()));

    if ($command_handlers->count() === 0) {
        if ($wants_help) {
            Output\line($help_text);
            return 0;
        }

        Output\error("There is no command in $commands_directory path!");

        return 1;
    }

    $command_index = 0;

    foreach ($inputs as $index => $input) {
        $input_string = implode(' ', array_slice($inputs->to_array(), 0, $index + 1));
        if (count(filter($command_handlers, fn (array $command_handler) => $command_handler['key'] === $input_string)) === 1) {
            $command_index = $index;
            break;
        }
    }

    $input_command = implode(' ', array_slice($inputs->to_array(), 0, $command_index + 1));
    $inputs = Input::make(array_slice($inputs->to_array(), $command_index + 1));

    if ($input_command === '') {
        Output\line($help_text);
        Output\write(PHP_EOL . 'Here you can see a list of available commands:' . PHP_EOL);

        $commands = reduce($command_handlers, function ($commands, array $command_handler) {
            $commands[$command_handler['key']] = first_line(docblock_to_text($command_handler['value']));
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

    $best_score = [-1, -1];
    $command_handler = reduce($command_handlers, function (array $carry, array $possible_command_handler) use (&$best_score, $input_command) {
        if (!str_starts_with($possible_command_handler['key'], $input_command)) {
            return $carry;
        }
        $command_words = explode(' ', $possible_command_handler['key']);
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
            return $carry;
        }

        $total_chars = strlen(implode('', $command_words));

        $score = [$matched, $total_chars];

        if ($score > $best_score) {
            $best_score = $score;
            $carry = $possible_command_handler;
        }

        return $carry;

    }, []);

    if (empty($command_handler)) {
        Output\error("Command $input_command not found!");

        return 1;
    }

    try {
        if ($wants_help) {
            Output\line(command_help($entrypoint, $input_command, $command_handler['value']));
            return 0;
        }

        Observer\send(RunningConsoleCommand::command($command_handler['key']));
        $return = execute($command_handler['value'], $inputs);
        $return = $return !== null ? $return : 0;
        Observer\send(CommandExecutionCompleted::successfully($command_handler['key'], $return));
        return $return;
    } catch (InvalidCommandPromptException $exception) {
        Output\error('Error: ' . $exception->getMessage());
        Output\line(command_help($entrypoint, $input_command, $command_handler['value']));
    } catch (InvalidCommandDefinitionException $exception) {
        Output\error('Error: ' . $exception->getMessage());
    }

    return 1;
}

/**
 * Execute a given command with the provided arguments.
 *
 * This function takes a handler representing the command and a set of arguments to be passed to the command.
 * It resolves the command parameters and their values and executes the command, returning the command's exit code.
 *
 * @param callable $command The callback representing the command to be executed.
 * @param Input $arguments The arguments to be passed to the command.
 *
 * @return int|null The exit code of the executed command, or null if the command does not return an exit code.
 *
 * @throws InvalidCommandPromptException If the provided arguments are invalid or missing.
 * @throws ReflectionException
 */
function execute(callable $command, Input $arguments): ?int
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
    }  else if (! empty($arguments->to_array())) {
        throw new InvalidCommandPromptException('You passed invalid argument to the command.');
    }

    $command_parameters = Map::from(map($command_parameters, fn (array $pair) => ['key' => $pair['key']->name, 'value' => $pair['value']]));

    return call_user_func_array($command, array_column($command_parameters->to_array(), 'value', 'key'));
}

/**
 * Generate a help message for a given command.
 *
 * This function takes the name of a command and its associated handler, and generates a help message
 * describing the command's usage, description, arguments, and options.
 *
 * @param string $entrypoint
 * @param string $name The name of the command.
 * @param callable $command The handler representing the command.
 *
 * @return string The generated help message for the command.
 * @throws ReflectionException
 */
function command_help(string $entrypoint, string $name, callable $command): string
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
Usage: $entrypoint $name$options_short_doc$arguments_short_doc

Description:
$description

Arguments:$arguments_doc

Options:$options_doc
EOD;
}
