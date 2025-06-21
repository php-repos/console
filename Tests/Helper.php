<?php

use PhpRepos\FileManager\Path;
use function PhpRepos\FileManager\Directories\delete_recursive;
use function PhpRepos\FileManager\Directories\preserve_copy_recursively;
use function PhpRepos\FileManager\Paths\realpath;

function copy_commands() {
    $helper_commands = realpath(__DIR__ . '/HelperCommands');
    $commands_directory = realpath(__DIR__ . '/../Source/Commands');
    preserve_copy_recursively($helper_commands, $commands_directory);
}

function delete_commands() {
    $commands_directory = Path::from_string(__DIR__ . '/../Source/Commands');
    delete_recursive($commands_directory);
}
