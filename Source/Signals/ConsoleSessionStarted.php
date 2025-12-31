<?php

namespace PhpRepos\Console\Signals;

use PhpRepos\Observer\API\Event;

class ConsoleSessionStarted extends Event
{
    public static function by(array $details): static
    {
        return static::create('A console session has been started.', $details);
    }
}
