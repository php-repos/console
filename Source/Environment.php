<?php

namespace PhpRepos\Console;

use PhpRepos\FileManager\Path;

/**
 * Class Environment
 *
 * Represents the environment configuration for a PHP console application.
 */
class Environment
{
    /**
     * Constructor for the Environment class.
     *
     * @param string $entry_point_name The name of the entry point script.
     * @param Config $config The configuration settings for the environment.
     */
    public function __construct(
        public readonly string $entry_point_name,
        public Config $config,
    ) {}

    /**
     * Create a new Environment instance for the console application.
     *
     * This method is typically used to create an Environment instance for a console application.
     *
     * @return static A new Environment instance.
     */
    public static function console(): static
    {
        return new static(
            Path::from_string($_SERVER['SCRIPT_FILENAME'])->leaf(),
            Config::default(),
        );
    }

    /**
     * Set the configuration settings for the environment.
     *
     * @param Config $config The configuration settings to set.
     * @return self The current Environment instance.
     */
    public function config(Config $config): self
    {
        $this->config = $config;

        return $this;
    }
}
