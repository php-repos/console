<?php

use PhpRepos\Console\Attributes\Description;
use PhpRepos\Console\Attributes\ExcessiveArguments;
use PhpRepos\Console\Attributes\LongOption;
use function PhpRepos\Cli\Output\line;

return function(
    #[LongOption('email')]
    #[Description('The email option for the command')]
    string $email,
    #[ExcessiveArguments]
    array $remaining,
) {
    line(implode('', $remaining));
};
