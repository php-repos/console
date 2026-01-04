<?php

namespace PhpRepos\Console\Solution\Execution;

use PhpRepos\Console\Solution\Data\CommandParameter;
use PhpRepos\Console\Solution\Data\Input;
use PhpRepos\Console\Solution\Exceptions\InvalidCommandPromptException;
use PhpRepos\Console\Infra\Reflections;
use PhpRepos\Console\Infra\Strings;
use ReflectionException;

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
    $parameters = Reflections\function_parameters($command);
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
                $hint = Strings\concat('|', $command_parameter->short_option, $command_parameter->long_option);
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
