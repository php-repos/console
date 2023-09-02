<?php

namespace PhpRepos\Console;

use PhpRepos\FileManager\Path;

/**
 * Configuration class for console applications.
 *
 * This class encapsulates configuration settings for a console application, including the default
 * commands directory, commands file suffix, and help text.
 */
class Config
{
    /**
     * Constructor for the Config class.
     *
     * @param Path $commands_directory The default directory where command files are located.
     * @param string $commands_file_suffix The suffix for command file names.
     * @param string $additional_supported_options Additional options doc, supported by the custom console.
     */
    public function __construct(
        public readonly Path $commands_directory,
        public readonly string $commands_file_suffix,
        public readonly string $additional_supported_options,
    ) {}

    public static function default(): static
    {
        return new static(
            commands_directory: Path::from_string($_SERVER['SCRIPT_FILENAME'])->sibling('Source/Commands'),
            commands_file_suffix: 'Command.php',
            additional_supported_options: '',
        );
    }
}
