<?php

use PhpRepos\FileManager\Path;
use function PhpRepos\FileManager\Directories\delete_recursive;
use function PhpRepos\FileManager\Directories\ls_all_recursively;
use function PhpRepos\FileManager\Directories\preserve_copy_recursively;
use function PhpRepos\FileManager\Paths\realpath;

function copy_commands() {
    $helper_commands = realpath(__DIR__ . '/HelperCommands');
    $commands_directory = realpath(__DIR__ . '/../Commands');
    preserve_copy_recursively($helper_commands, $commands_directory);
    foreach (ls_all_recursively($commands_directory) as $path) {
        if (is_file($path) && str_ends_with($path, '.phpsample')) {
            rename($path, str_replace('.phpsample', '.php', $path));
        }
    }
}

function delete_commands() {
    $commands_directory = Path::from_string(__DIR__ . '/../Commands');
    delete_recursive($commands_directory);
}
