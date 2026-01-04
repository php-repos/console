<?php

namespace PhpRepos\Console\Business\Signals;

use PhpRepos\Observer\API\Event;

class CommandExecutionCompleted extends Event
{
    public static function successfully(string $command, int $exit_code): static
    {
        return static::create('Command execution completed successfully.', [
            'command' => $command,
            'exit_code' => $exit_code,
        ]);
    }
}
