<?php

namespace PhpRepos\Console;

use Closure;
use PhpRepos\Datatype\Collection;
use ReflectionException;
use ReflectionParameter;
use function PhpRepos\Console\Reflection\function_parameters;
use function PhpRepos\Datatype\Arr\map;

/**
 * Parameter collection class for command-line command parameters.
 *
 * This class extends the Collection class and provides a convenient way to manage a collection
 * of command parameters extracted from a Closure representing a console command.
 */
class ParamCollection extends Collection
{
    /**
     * Create a ParamCollection instance from a Closure representing a console command.
     *
     * This method extracts and processes the parameters of a Closure representing a console command
     * and creates a ParamCollection containing CommandParameter instances.
     *
     * @param Closure $command The Closure representing the console command.
     *
     * @return static A new ParamCollection containing CommandParameter instances.
     * @throws ReflectionException
     */
    public static function from(Closure $command): static
    {
        $parameters = function_parameters($command);
        return new static(map($parameters, fn (ReflectionParameter $param) => CommandParameter::create($param)));
    }
}
