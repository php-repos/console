<?php

namespace PhpRepos\Console\Signals;

use PhpRepos\FileManager\Path;
use PhpRepos\Observer\Signals\Plan;

class RunningConsoleCommand extends Plan
{
    public static function from_path(Path $path): static
    {
        return static::create('Executing a command', ['command' => $path->string()]);
    }
}