<?php

namespace PhpRepos\Console;

use PhpRepos\Datatype\Map;

class CommandHandlers extends Map
{
    public function add(string $name, callable $handler): static
    {
        return $this->put($name, $handler);
    }
}
