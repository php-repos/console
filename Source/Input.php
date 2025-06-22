<?php

namespace PhpRepos\Console;

use PhpRepos\Console\Exceptions\InvalidCommandPromptException;
use PhpRepos\Datatype\Collection;
use function PhpRepos\Datatype\Arr\first;
use function PhpRepos\Datatype\Arr\first_key;
use function PhpRepos\Datatype\Arr\last;
use function PhpRepos\Datatype\Arr\reduce;

class Input extends Collection
{
    /**
     * Take an argument based on the specified parameter definition.
     *
     * This method takes an argument from the list of command-line arguments
     * based on the provided CommandParameter object.
     *
     * @param CommandParameter $parameter The parameter definition.
     *
     * @return null|string|array The extracted argument or null if not found.
     * @throws InvalidCommandPromptException If the argument is invalid.
     */
    public function take_argument(CommandParameter $parameter): null|string|array
    {
        if ($parameter->type === 'array') {
            $value = $this->take_all();
        } else if ($parameter->type === 'bool') {
            $value = $this->take_first_one();

            if ($value === 'true') {
                $value = true;
            } else if ($value === 'false') {
                $value = false;
            } else if (is_null($value)) {
                $value = null;
            } else {
                throw new InvalidCommandPromptException('Bool argument accepts true or false.');
            }
        } else {
            $value = $this->take_first_one();
        }

        return $value;
    }

    /**
     * Take an option based on the specified parameter definition.
     *
     * This method takes an option (e.g., --verbose or -v) from the list of
     * command-line arguments based on the provided CommandParameter object.
     *
     * @param CommandParameter $parameter The parameter definition.
     *
     * @return null|bool|string|array The extracted option or null if not found.
     * @throws InvalidCommandPromptException If the option is invalid.
     */
    public function take_option(CommandParameter $parameter): null|bool|string|array
    {
        return match ($parameter->type) {
            'array' => $this->take_option_as_array($parameter),
            'bool' => $this->take_option_as_bool($parameter),
            default => $this->take_option_as_string($parameter),
        };
    }

    /**
     * Takes any remaining items.
     *
     * @return array
     */
    public function take_all(): array
    {
        $temp = $this->items;
        $this->items = [];

        return $temp;
    }

    private function take_option_as_array(CommandParameter $parameter)
    {
        return reduce($this->items, function ($options, $argument, $index) use ($parameter) {
            if (str_starts_with($argument, '-')) {
                if (str_starts_with($argument, '--')) {
                    if (str_starts_with($argument, "--$parameter->long_option=")) {
                        $options[] = explode("--$parameter->long_option=", $argument)[1];
                        $this->use($index);
                    }
                } else if (str_starts_with($argument, "-$parameter->short_option=")) {
                    $options[] = explode("-$parameter->short_option=", $argument)[1];
                    $this->use($index);
                }
            }

            return $options;
        });
    }

    private function last_option_index(CommandParameter $parameter): ?int
    {
        $option_indexes = $this->option_indexes($parameter);

        return empty($option_indexes) ? null : last($option_indexes);
    }

    private function take_option_as_bool(CommandParameter $parameter): ?bool
    {
        $option_index = $this->last_option_index($parameter);

        if ($parameter->is_optional && $option_index === null) {
            return null;
        }

        if ($option_index !== null) {
            $argument = $this->items[$option_index];

            if ($parameter->long_option && str_starts_with($argument, "--$parameter->long_option=")) {
                throw new InvalidCommandPromptException("Long option `$parameter->long_option` must be boolean and does not accept values.");
            }

            if ($parameter->short_option && str_starts_with($argument, "-$parameter->short_option=")) {
                throw new InvalidCommandPromptException("Short option `$parameter->short_option` must be boolean and does not accept values.");
            }

            $this->use($option_index);

            return true;
        }

        return false;
    }

    private function take_option_as_string(CommandParameter $parameter): ?string
    {
        $option_indexes = $this->option_indexes($parameter);
        $option_index = $this->last_option_index($parameter);

        if ($option_index === null) {
            return null;
        }

        $value = null;

        if (str_starts_with($this->items[$option_index], "--$parameter->long_option=")) {
            $value = explode("--$parameter->long_option=", $this->items[$option_index])[1];
        } else if ($this->items[$option_index] === "--$parameter->long_option") {
            if (! isset($this->items[$option_index + 1])) {
                throw new InvalidCommandPromptException('Option needs value.');
            }
            $value = $this->items[$option_index + 1];
        } else if (str_starts_with($this->items[$option_index], "-$parameter->short_option=")) {
            $value = explode("-$parameter->short_option=", $this->items[$option_index])[1];
        } else if ($this->items[$option_index] === "-$parameter->short_option") {
            if (! isset($this->items[$option_index + 1])) {
                throw new InvalidCommandPromptException('Option needs value.');
            }
            $value = $this->items[$option_index + 1];
        }

        foreach ($option_indexes as $index) {
            if (str_contains($this->items[$index], '=')) {
                $this->use($index);
            } else {
                $this->use($index)->use($index + 1);
            }
        }

        return $value;
    }

    private function option_indexes(CommandParameter $parameter): array
    {
        return reduce($this->items, function ($option_indexes, $argument, $index) use ($parameter) {
            if (
                $parameter->long_option && (
                    str_starts_with($argument, "--$parameter->long_option=")
                    || $argument === "--$parameter->long_option"
                )
            ) {
                $option_indexes[] = $index;
            }

            if (
                $parameter->short_option && (
                    str_starts_with($argument, "-$parameter->short_option=")
                    || $argument === "-$parameter->short_option"
                )
            ) {
                $option_indexes[] = $index;
            }

            return $option_indexes;
        }, []);
    }

    private function use(int $index): self
    {
        unset($this->items[$index]);

        return $this;
    }

    private function take_first_one(): ?string
    {
        if (empty($this->items)) {
            return null;
        }

        $value = first($this->items);
        $this->use(first_key($this->items));

        return $value;
    }
}
