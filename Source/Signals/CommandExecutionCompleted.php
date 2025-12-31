<?php

namespace PhpRepos\Console\Signals;

use PhpRepos\Observer\API\Event;

class CommandExecutionCompleted extends Event
{
    public static function successfully(string $command, int $result_code): static
    {
        return static::create('Command execution is complete successfully.', ['command' => $command, 'result_code' => $result_code]);
    }
}
