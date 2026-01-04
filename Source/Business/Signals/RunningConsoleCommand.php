<?php

namespace PhpRepos\Console\Business\Signals;

use PhpRepos\Observer\API\Plan;

class RunningConsoleCommand extends Plan
{
    public static function command(string $command): static
    {
        return static::create('Running a console command.', ['command' => $command]);
    }
}
