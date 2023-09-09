<?php

use PhpRepos\Console\Attributes\Argument;
use PhpRepos\Console\Attributes\Description;
use PhpRepos\Console\Attributes\LongOption;
use PhpRepos\Console\Attributes\ShortOption;
use function PhpRepos\Cli\IO\Write\line;

/**
 * This is the full-fledged command
 * It uses both options and arguments
 * Example: console full-fledged --email=info@phpkg.com -u JohnDoe password -f user customer supplier
 */
return function(
    #[LongOption('email')]
    #[Description('The required email option')]
    string $email,
    #[ShortOption('u')]
    #[Description('The required username option')]
    string $username,
    #[Argument, LongOption('password'), Description('The password to be passed using option or argument')]
    string $password,
    #[LongOption('force'), ShortOption('f'),Description('Optional force option')]
    ?bool $force = null,
    #[Argument]
    #[Description('List of rules for user')]
    ?array $roles = [],
) {
    $force = $force === null ? 'null' : ($force === true ? 'true' : 'false');

    line("Email: $email, Username: $username, Password: $password, Roles: " . implode(', ', $roles) . " Force: $force");
};
