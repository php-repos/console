<?php

namespace PhpRepos\Console\Business\Finder;

use PhpRepos\Console\Business\Outcome;
use PhpRepos\Console\Solution\Paths;
use PhpRepos\Console\Solution\Handlers;
use Throwable;
use function PhpRepos\Observer\API\Bus\propose;
use function PhpRepos\Observer\API\Bus\broadcast;
use function PhpRepos\Observer\API\Signals\plan;
use function PhpRepos\Observer\API\Signals\event;

/**
 * Discover commands from a directory path.
 *
 * Business specification: Find all command handlers in a directory.
 *
 * @param string $root Full path to commands directory
 * @param string $commands_file_suffix File suffix (default: 'Command.php')
 * @return Outcome
 *   - success: true, data['handlers' => array of command handlers]
 *   - failure: false, message => error description
 */
function path(string $root, string $commands_file_suffix): Outcome
{
    try {
        propose(plan('Finding commands from path.', ['root' => $root]));

        if (!Paths\exists($root)) {
            broadcast(event('Commands directory does not exist.', ['directory' => $root]));
            return new Outcome(
                false,
                "Commands directory does not exist: $root",
                ['handlers' => []]
            );
        }

        $files = Paths\get_all_matching_files($root, $commands_file_suffix);

        $command_handlers = [];
        foreach ($files as $command_file) {
            $command_name = Handlers\guess_command_name($root, $command_file, $commands_file_suffix);
            $command_handlers[$command_name] = require $command_file;
        }

        if (count($command_handlers) === 0) {
            broadcast(event('No commands found in directory.', ['directory' => $root]));
            return new Outcome(
                false,
                "No commands found in directory: $root",
                ['handlers' => []]
            );
        }

        broadcast(event('Commands discovered successfully.', ['directory' => $root, 'count' => count($command_handlers)]));
        return new Outcome(true, 'Commands found successfully', [
            'handlers' => $command_handlers,
        ]);
    } catch (Throwable $e) {
        broadcast(event('Failed to find commands.', ['error' => $e->getMessage()]));
        return new Outcome(
            false,
            "Failed to find commands: {$e->getMessage()}",
            ['handlers' => [], 'help_lines' => $e->getTrace()]
        );
    }
}
