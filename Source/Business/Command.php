<?php

namespace PhpRepos\Console\Business\Command;

use PhpRepos\Console\Business\Outcome;
use PhpRepos\Console\Business\Signals\RunningConsoleCommand;
use PhpRepos\Console\Business\Signals\CommandExecutionCompleted;
use PhpRepos\Console\Business\Signals\CommandExecutionFailed;
use PhpRepos\Console\Solution\Handlers;
use PhpRepos\Console\Solution\Inputs;
use PhpRepos\Console\Solution\Execution;
use PhpRepos\Console\Solution\Exceptions\InvalidCommandPromptException;
use PhpRepos\Console\Solution\Exceptions\InvalidCommandDefinitionException;
use Throwable;
use function PhpRepos\Observer\API\Bus\propose;
use function PhpRepos\Observer\API\Bus\broadcast;
use function PhpRepos\Observer\API\Signals\plan;
use function PhpRepos\Observer\API\Signals\event;

/**
 * Find a command handler matching the given inputs.
 *
 * Searches through available command handlers to find the best match for the provided inputs.
 * Returns the matched command name and its handler, or an error if no match is found.
 *
 * @param array $command_handlers Associative array of command names to their handlers
 * @param array $inputs Array of command-line input arguments
 * @return Outcome Success with ['name' => string, 'handler' => callable] or failure with error message
 */
function find(array $command_handlers, array $inputs): Outcome
{
    try {
        propose(plan('Finding command from inputs.', ['inputs' => $inputs]));

        $input_command = Handlers\command($inputs, $command_handlers);

        if ($input_command === '') {
            broadcast(event('No command specified in inputs.'));
            return new Outcome(false, 'No command specified!', []);
        }

        $matched_command = Handlers\match_best_command_from_inputs($input_command, $command_handlers);

        if (empty($matched_command)) {
            broadcast(event('No matching command found.', ['input_command' => $input_command]));
            return new Outcome(false, "Command $input_command not found!", []);
        }

        broadcast(event('Command found.', ['command' => $matched_command['key']]));
        return new Outcome(true, '', [
            'name' => $matched_command['key'],
            'handler' => $matched_command['value'],
        ]);
    } catch (Throwable $e) {
        broadcast(event('Failed to find command.', ['error' => $e->getMessage()]));
        return new Outcome(false, "Failed to find command: {$e->getMessage()}", []);
    }
}

/**
 * Get a list of all available commands with their descriptions.
 *
 * Returns an array of all registered commands, each containing the command name
 * and its short description extracted from the command handler's docblock.
 *
 * @param array $command_handlers Associative array of command names to their handlers
 * @return Outcome Success with ['commands' => array] where each command has 'name' and 'description'
 */
function all(array $command_handlers): Outcome
{
    try {
        propose(plan('Getting all commands.'));

        $commands = [];
        foreach ($command_handlers as $command_name => $command_handler) {
            $commands[] = [
                'name' => $command_name,
                'description' => Handlers\description($command_handler),
            ];
        }

        broadcast(event('Commands list generated.', ['count' => count($commands)]));
        return new Outcome(true, '', [
            'commands' => $commands,
        ]);
    } catch (Throwable $e) {
        broadcast(event('Failed to get commands.', ['error' => $e->getMessage()]));
        return new Outcome(false, "Failed to get commands: {$e->getMessage()}", []);
    }
}

/**
 * Get detailed information about a command.
 *
 * Extracts and returns comprehensive information about a command including its
 * full description, accepted arguments, and available options.
 *
 * @param callable $command The command handler to describe
 * @return Outcome Success with ['description' => string, 'arguments' => array, 'options' => array]
 */
function describe(callable $command): Outcome
{
    try {
        propose(plan('Describing command.'));

        $arguments = Handlers\get_arguments($command);
        $options = Handlers\get_options($command);
        $description = Handlers\get_description($command);

        broadcast(event('Command described.', [
            'description' => $description,
            'arguments' => $arguments,
            'options' => $options,
        ]));
        return new Outcome(true, '', [
            'description' => $description,
            'arguments' => $arguments,
            'options' => $options,
        ]);
    } catch (Throwable $e) {
        broadcast(event('Failed to describe command.', ['error' => $e->getMessage()]));
        return new Outcome(false, "Failed to describe command: {$e->getMessage()}", []);
    }
}

/**
 * Execute a command with the given inputs.
 *
 * Runs the specified command handler with the provided inputs, handling input extraction
 * and error management. Returns the command's exit code upon completion.
 *
 * @param string $command_name Name of the command being executed
 * @param callable $command_handler The command handler to execute
 * @param array $command_handlers All available command handlers (used for input extraction)
 * @param array $inputs Array of command-line input arguments
 * @return Outcome Success with ['exit_code' => int] or failure with error message and exit code
 */
function run(string $command_name, callable $command_handler, array $command_handlers, array $inputs): Outcome
{
    try {
        propose(RunningConsoleCommand::command($command_name));

        $command_index = Inputs\command_index($inputs, $command_handlers);
        $remaining_inputs = Inputs\extract_remaining_inputs($inputs, $command_index);

        $exit_code = Execution\execute($command_handler, $remaining_inputs);
        $exit_code = $exit_code !== null ? $exit_code : 0;

        broadcast(CommandExecutionCompleted::successfully($command_name, $exit_code));

        return new Outcome(true, '', [
            'exit_code' => $exit_code,
        ]);

    } catch (InvalidCommandPromptException | InvalidCommandDefinitionException $exception) {
        broadcast(CommandExecutionFailed::with($command_name, $exception->getMessage()));

        return new Outcome(false, 'Error: ' . $exception->getMessage(), [
            'exit_code' => 1,
        ]);
    } catch (Throwable $e) {
        broadcast(CommandExecutionFailed::with($command_name, $e->getMessage()));

        return new Outcome(false, "Failed to execute command: {$e->getMessage()}", [
            'exit_code' => 1,
        ]);
    }
}
