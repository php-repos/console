<?php

namespace PhpRepos\Console;

use PhpRepos\Console\Attributes\Argument;
use PhpRepos\Console\Attributes\ExcessiveArguments;
use PhpRepos\Console\Attributes\LongOption;
use PhpRepos\Console\Attributes\Description;
use PhpRepos\Console\Attributes\ShortOption;
use PhpRepos\Console\Exceptions\InvalidCommandDefinitionException;
use ReflectionParameter;
use function PhpRepos\Console\Reflection\attribute_property;
use function PhpRepos\Console\Reflection\has_attribute;
use function PhpRepos\Console\Reflection\is_builtin;

/**
 * Represents a parameter definition for a command.
 *
 * This class encapsulates information about a command's parameter, including its
 * name, type, whether it accepts an argument, and related options.
 */
class CommandParameter
{
    /**
     * Constructor for the CommandParameter class.
     *
     * @param string      $name                      The name of the parameter.
     * @param bool        $is_optional               Indicates if the parameter is optional.
     * @param bool        $accepts_argument          Indicates if the parameter accepts an argument.
     * @param string      $type                      The data type of the parameter.
     * @param string      $option_class              The class representing the option, if applicable.
     * @param bool        $is_option                 Indicates if the parameter is an option.
     * @param bool        $wants_excessive_arguments Indicates if the parameter has been defined to get excessive arguments
     * @param null|string $short_option              The short option flag, if defined.
     * @param null|string $long_option               The long option flag, if defined.
     * @param null|string $description               A description of the parameter, if available.
     * @param mixed       $default_value             The default value of the parameter, if available.
     */
    public function __construct(
        public readonly string  $name,
        public readonly bool    $is_optional,
        public readonly bool    $accepts_argument,
        public readonly string  $type,
        public readonly string  $option_class,
        public readonly bool    $is_option,
        public readonly bool    $wants_excessive_arguments,
        public readonly ?string $short_option,
        public readonly ?string $long_option,
        public readonly ?string $description,
        public readonly mixed $default_value,
    ) {}

    /**
     * Create a CommandParameter instance from a ReflectionParameter object.
     *
     * This method constructs a CommandParameter instance based on the information
     * extracted from a ReflectionParameter object.
     *
     * @param ReflectionParameter $param The ReflectionParameter object to create from.
     *
     * @return static A new CommandParameter instance.
     *
     * @throws InvalidCommandDefinitionException If the parameter definition is invalid.
     */
    public static function create(ReflectionParameter $param): static
    {
        if (is_null($param->getType())) {
            throw new InvalidCommandDefinitionException('Command\'s parameter must have type.');
        }
        if (! is_builtin($param)) {
            throw new InvalidCommandDefinitionException('Command options must be builtin type (bool, string, int, array).');
        }

        $short_option = attribute_property($param, ShortOption::class, 'option');
        $long_option = attribute_property($param, LongOption::class, 'option');
        $accepts_argument = has_attribute($param, Argument::class);
        $wants_excessive_arguments = has_attribute($param, ExcessiveArguments::class);

        if (! $short_option && ! $long_option && ! $accepts_argument && ! $wants_excessive_arguments) {
            throw new InvalidCommandDefinitionException('No option or argument has been defined.');
        }

        return new static(
            name: $param->getName(),
            is_optional: $param->isOptional(),
            accepts_argument: $accepts_argument,
            type: $param->getType()->getName(),
            option_class: $param->getType()->getName(),
            is_option: $short_option || $long_option,
            wants_excessive_arguments: $wants_excessive_arguments,
            short_option: $short_option,
            long_option: $long_option,
            description: attribute_property($param, Description::class, 'description'),
            default_value: $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
        );
    }
}
