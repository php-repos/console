<?php

namespace PhpRepos\Console\Signals;

use PhpRepos\Observer\Signals\Plan;

class RunningConsoleCommand extends Plan
{
    public static function command(string $command): static
    {
        return static::create('Executing a command', ['command' => $command]);
    }
}