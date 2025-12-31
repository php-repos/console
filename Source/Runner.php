<?php

namespace PhpRepos\Console\Runner;

use PhpRepos\Console\Solutions\Data\CommandHandlers;
use PhpRepos\Console\Solutions\Data\CommandParameter;
use PhpRepos\Console\Exceptions\InvalidCommandDefinitionException;
use PhpRepos\Console\Exceptions\InvalidCommandPromptException;
use PhpRepos\Console\Solutions\Data\Input;
use PhpRepos\Console\Signals\CommandExecutionCompleted;
use PhpRepos\Console\Signals\RunningConsoleCommand;
use PhpRepos\Console\Signals\ConsoleSessionStarted;
use PhpRepos\Observer\API\Bus;
use ReflectionException;
use ReflectionParameter;
use function PhpRepos\Console\Infra\CLI\error;
use function PhpRepos\Console\Infra\CLI\line;
use function PhpRepos\Console\Infra\CLI\write;
use function PhpRepos\Console\Infra\Reflections\docblock_to_text;
use function PhpRepos\Console\Infra\Reflections\function_parameters;
use function PhpRepos\Console\Infra\Arrays\any;
use function PhpRepos\Console\Infra\Arrays\filter;
use function PhpRepos\Console\Infra\Arrays\map;
use function PhpRepos\Console\Infra\Arrays\max_key_length;
use function PhpRepos\Console\Infra\Arrays\reduce;
use function PhpRepos\Console\Infra\Strings\after_first_occurrence;
use function PhpRepos\Console\Infra\Strings\concat;
use function PhpRepos\Console\Infra\Strings\first_line;
use function PhpRepos\Console\Infra\Strings\kebab_case;
use function PhpRepos\Console\Infra\Filesystem\exists;
use function PhpRepos\Console\Infra\Filesystem\ls_all_recursively;
use function PhpRepos\Console\Infra\Strings\prepend_when_exists;

/**
 * Loads commands from the given commands path
 *
 * @param string $root
 * @param string $commands_file_suffix
 * @return CommandHandlers
 */
function from_path(string $root, string $commands_file_suffix = 'Command.php'): CommandHandlers
{
    $command_handlers = new CommandHandlers();

    if (!exists($root)) {
        return $command_handlers;
    }

    $files = filter(ls_all_recursively($root), fn (string $path) => is_file($path) && str_ends_with($path, $commands_file_suffix));

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
 * @param string $commands_directory
 * @return int
 * @throws ReflectionException
 */
function run(CommandHandlers $command_handlers, Input $inputs, string $entrypoint, string $help_text, bool $wants_help, string $commands_directory): int
{
    Bus\send(ConsoleSessionStarted::by($inputs->to_array()));

    if ($command_handlers->count() === 0) {
        if ($wants_help) {
            line($help_text);
            return 0;
        }

        error("There is no command in $commands_directory path!");

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
        line($help_text);
        write(PHP_EOL . 'Here you can see a list of available commands:' . PHP_EOL);

        $commands = reduce($command_handlers, function ($commands, array $command_handler) {
            $commands[$command_handler['key']] = first_line(docblock_to_text($command_handler['value']));
            return $commands;
        }, []);

        $max_key_length = max_key_length($commands);

        foreach ($commands as $command => $description) {
            $description
                ? line('    ' . str_pad($command, $max_key_length + 4) . $description)
                : line("    $command");
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
        error("Command $input_command not found!");

        return 1;
    }

    try {
        if ($wants_help) {
            line(command_help($entrypoint, $input_command, $command_handler['value']));
            return 0;
        }

        Bus\send(RunningConsoleCommand::command($command_handler['key']));
        $return = execute($command_handler['value'], $inputs);
        $return = $return !== null ? $return : 0;
        Bus\send(CommandExecutionCompleted::successfully($command_handler['key'], $return));
        return $return;
    } catch (InvalidCommandPromptException $exception) {
        error('Error: ' . $exception->getMessage());
        line(command_help($entrypoint, $input_command, $command_handler['value']));
    } catch (InvalidCommandDefinitionException $exception) {
        error('Error: ' . $exception->getMessage());
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
    // Build array with parameter name as key, containing both the CommandParameter and its value
    $command_parameters = [];
    foreach ($parameters as $param) {
        $command_param = CommandParameter::create($param);
        $command_parameters[$command_param->name] = [
            'parameter' => $command_param,
            'value' => null
        ];
    }

    // First pass: extract options
    foreach ($command_parameters as $name => &$item) {
        $command_parameter = $item['parameter'];
        if ($command_parameter->is_option && !$command_parameter->wants_excessive_arguments) {
            $item['value'] = $arguments->take_option($command_parameter);
        }
    }
    unset($item);

    // Second pass: extract arguments and validate
    foreach ($command_parameters as $name => &$item) {
        $command_parameter = $item['parameter'];
        $value = $item['value'];

        if ($command_parameter->wants_excessive_arguments) {
            continue;
        }

        if ($value === null) {
            if ($command_parameter->accepts_argument) {
                $value = $arguments->take_argument($command_parameter);
            }

            $value = is_null($value) ? $command_parameter->default_value : $value;
        }

        if ($value === null && $command_parameter->type !== 'bool' && !$command_parameter->is_optional) {
            if ($command_parameter->accepts_argument) {
                $message = "Argument `$command_parameter->name` is required.";
            } else {
                $hint = concat('|', $command_parameter->short_option, $command_parameter->long_option);
                $message = "Option `$hint` is required.";
            }

            throw new InvalidCommandPromptException($message);
        }

        $item['value'] = $value;
    }
    unset($item);

    // Handle excessive arguments
    $has_excessive = false;
    foreach ($command_parameters as $item) {
        if ($item['parameter']->wants_excessive_arguments) {
            $has_excessive = true;
            break;
        }
    }

    if ($has_excessive) {
        $excessive_values = $arguments->take_all();
        foreach ($command_parameters as $name => &$item) {
            if ($item['parameter']->wants_excessive_arguments) {
                $item['value'] = $excessive_values;
            }
        }
        unset($item);
    } else if (!empty($arguments->to_array())) {
        throw new InvalidCommandPromptException('You passed invalid argument to the command.');
    }

    // Extract just the values by parameter name for function call
    $values = [];
    foreach ($command_parameters as $name => $item) {
        $values[$name] = $item['value'];
    }

    return call_user_func_array($command, $values);
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

    $arguments = map(filter($command_parameters, fn (CommandParameter $command_parameter) => $command_parameter->accepts_argument), function (CommandParameter $command_parameter) {
        $key = ($command_parameter->is_optional || $command_parameter->is_option) ? "[<$command_parameter->name>]" : "<$command_parameter->name>";

        return ['key' => $key, 'value' => $command_parameter->description];
    });

    $options = map(filter($command_parameters, fn (CommandParameter $command_parameter) => $command_parameter->is_option), function (CommandParameter $command_parameter) {
        $short_option = prepend_when_exists($command_parameter->short_option, '-');
        $long_option = prepend_when_exists($command_parameter->long_option, '--');

        $key = concat(', ', $short_option, $long_option);

        if ($command_parameter->type !== 'bool') {
            $key .= $command_parameter->is_optional ? " [<$command_parameter->name>]" : " <$command_parameter->name>";
        }

        return ['key' => $key, 'value' => $command_parameter->description];
    });

    $arguments_short_doc = reduce($arguments, function ($arguments_doc, array $pair) {
        return $arguments_doc . ' ' . $pair['key'];
    }, '');

    if (count($arguments) === 0) {
        $arguments_doc = PHP_EOL . 'This command does not accept any arguments.';
    } else {
        $arguments_max_key_length = max(map($arguments, fn (array $pair) => strlen($pair['key'])));
        $arguments_doc = reduce($arguments, function ($arguments_doc, $pair) use ($arguments_max_key_length) {
            return $arguments_doc . PHP_EOL . '  ' . str_pad($pair['key'], $arguments_max_key_length) . ($pair['value'] ? ' ' . $pair['value'] : '');
        }, '');
    }

    if (count($options) === 0) {
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
