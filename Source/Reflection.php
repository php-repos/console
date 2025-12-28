<?php

namespace PhpRepos\Console\Reflection;

use Closure;
use ReflectionException;
use ReflectionFunction;
use ReflectionParameter;
use function PhpRepos\Console\Infra\Strings\after_first_occurrence;

/**
 * Extract the description from a docblock comment.
 *
 * This function takes a docblock comment and extracts the description text.
 *
 * @param Closure $function The function with a docblock comment.
 *
 * @return string The extracted description text from the docblock comment.
 *
 * @throws ReflectionException If reflection on the function fails.
 */
function docblock_to_text(Closure $function): string
{
    $docblock = doc_block($function);

    // Split the input string into an array of lines
    $lines = explode(PHP_EOL, $docblock);

    // Remove the first and last lines
    array_shift($lines); // Remove the first line
    array_pop($lines);   // Remove the last line

    // Trim " *" from the beginning of each line
    $processed_lines = array_map(function($line) {
        return after_first_occurrence($line, '*');
    }, $lines);

    // Join the processed lines back into a single string
    return implode(PHP_EOL, $processed_lines);
}

/**
 * Get the docblock comment of a Closure or function.
 *
 * This function retrieves the docblock comment of a Closure or function.
 *
 * @param Closure|string $function The Closure or function for which to retrieve the docblock.
 *
 * @return string The docblock comment associated with the Closure or function.
 *
 * @throws ReflectionException If reflection on the function fails.
 */
function doc_block(Closure|string $function): string
{
    $reflection = new ReflectionFunction($function);
    return $reflection->getDocComment();
}

/**
 * Get the parameters of a Closure or function.
 *
 * This function retrieves the parameters of a Closure or function and returns an array of ReflectionParameter objects.
 *
 * @param Closure|string $function The Closure or function for which to retrieve parameters.
 *
 * @return array An array of ReflectionParameter objects representing the function's parameters.
 *
 * @throws ReflectionException If reflection on the function fails.
 */
function function_parameters(Closure|string $function): array
{
    $reflection = new ReflectionFunction($function);
    return $reflection->getParameters();
}

/**
 * Get the value of a specific property from an attribute applied to a parameter.
 *
 * This function retrieves the value of a specified property from an attribute applied to a ReflectionParameter.
 *
 * @param ReflectionParameter $parameter The ReflectionParameter object.
 * @param string $attribute The attribute class name.
 * @param string $property The name of the property to retrieve.
 *
 * @return mixed|null The value of the specified property from the attribute or null if the attribute is not present.
 */
function attribute_property(ReflectionParameter $parameter, string $attribute, string $property): mixed
{
    $attributes = $parameter->getAttributes($attribute);

    if (!empty($attributes)) {
        $desired_attribute = $attributes[0];
        $attribute_instance = $desired_attribute->newInstance();

        return $attribute_instance->$property;

    }

    return null;
}

/**
 * Check if a ReflectionParameter has a specific attribute.
 *
 * This function checks whether a ReflectionParameter has a specific attribute applied to it.
 *
 * @param ReflectionParameter $parameter The ReflectionParameter object.
 * @param string|object $attribute The attribute class name or instance.
 *
 * @return bool True if the attribute is applied to the parameter; otherwise, false.
 */
function has_attribute(ReflectionParameter $parameter, string|object $attribute): bool
{
    return isset($parameter->getAttributes($attribute)[0]);
}

/**
 * Check if a parameter's type is a built-in (primitive) PHP type.
 *
 * This function checks if a parameter's type is a built-in (primitive) PHP type.
 *
 * @param ReflectionParameter $parameter The ReflectionParameter object.
 *
 * @return bool True if the parameter's type is a built-in PHP type; otherwise, false.
 */
function is_builtin(ReflectionParameter $parameter): bool
{
    return $parameter->getType()->isBuiltin();
}
