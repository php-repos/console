<?php

use PhpRepos\Console\Attributes\Description;
use PhpRepos\Console\Attributes\LongOption;
use PhpRepos\Console\Attributes\ShortOption;
use function PhpRepos\Cli\IO\Write\line;

return function (
    #[LongOption('email')]
    #[Description('The email option for the command')]
    string $email,
    #[LongOption('username')]
    #[ShortOption('u')]
    #[Description('The username option for the command')]
    ?string $username = null
) {
    $username = $username ?: 'default';
    line("This is the given email option: $email and the given username is $username");
};
