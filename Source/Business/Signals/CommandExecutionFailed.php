<?php

namespace PhpRepos\Console\Business\Signals;

use PhpRepos\Observer\API\Event;

class CommandExecutionFailed extends Event
{
    public static function with(string $command, string $error): static
    {
        return static::create('Command execution failed.', [
            'command' => $command,
            'error' => $error,
        ]);
    }
}
