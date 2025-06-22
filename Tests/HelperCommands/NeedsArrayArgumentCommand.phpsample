<?php

use PhpRepos\Console\Attributes\Argument;
use PhpRepos\Console\Attributes\LongOption;
use function PhpRepos\Cli\Output\line;

return function (
    #[LongOption('email')]
    string $email,
    #[Argument]
    string $username,
    #[Argument]
    array $ids,
) {
    line("Email: $email, Username: $username, IDs: " . implode(' and ', $ids));
};
