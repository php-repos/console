<?php

namespace PhpRepos\Console;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class CommandHandlers implements IteratorAggregate, Countable
{
    protected array $items = [];

    public function add(string $name, callable $handler): static
    {
        if (!isset($this->items[$name])) {
            $this->items[$name] = $handler;
        }

        return $this;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        // Return items in the format expected by Runner.php (array with 'key' and 'value')
        $formatted = [];
        foreach ($this->items as $key => $value) {
            $formatted[] = ['key' => $key, 'value' => $value];
        }
        return new ArrayIterator($formatted);
    }

    public function to_array(): array
    {
        return $this->items;
    }
}
