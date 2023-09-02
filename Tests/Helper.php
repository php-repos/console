<?php

namespace Tests\Helper;

use PhpRepos\FileManager\Path;
use function PhpRepos\FileManager\Directory\delete_recursive;
use function PhpRepos\FileManager\Directory\make;
use function PhpRepos\FileManager\Directory\preserve_copy_recursively;

function copy_commands() {
    $helper_commands = Path::from_string(__DIR__ . '/HelperCommands');
    $commands_directory = Path::from_string(__DIR__ . '/../Source/Commands');
    make($commands_directory);
    preserve_copy_recursively($helper_commands, $commands_directory);
}

function delete_commands() {
    $commands_directory = Path::from_string(__DIR__ . '/../Source/Commands');
    delete_recursive($commands_directory);
}
