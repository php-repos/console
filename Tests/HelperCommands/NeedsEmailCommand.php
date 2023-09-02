<?php

use PhpRepos\Console\Attributes\Description;
use PhpRepos\Console\Attributes\LongOption;
use function PhpRepos\Cli\IO\Write\line;

return function (
    #[LongOption('email')]
    #[Description('The email option for the command')]
    string $email
) {
    line("This is the given email option: $email");
};
