<?php

namespace PhpRepos\Console\Signals;

use PhpRepos\FileManager\Path;
use PhpRepos\Observer\Signals\Event;

class CommandExecutionCompleted extends Event
{
    public static function successfully(Path $command, int $result_code): static
    {
        return static::create('Command execution is complete successfully.', ['command' => $command->string(), 'result_code' => $result_code]);
    }
}
