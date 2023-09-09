<?php

use PhpRepos\Console\Attributes\Description;
use PhpRepos\Console\Attributes\LongOption;
use PhpRepos\Console\Attributes\ShortOption;
use function PhpRepos\Cli\IO\Write\line;

return function (
    #[LongOption('username')]
    #[ShortOption('u')]
    #[Description('The username option for the command')]
    string $username
) {
    line("This is the given username option: $username");
};
