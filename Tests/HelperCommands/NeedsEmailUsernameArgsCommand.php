<?php

use PhpRepos\Console\Attributes\Argument;
use PhpRepos\Console\Attributes\Description;
use function PhpRepos\Cli\IO\Write\line;

return function (
    #[Argument]
    #[Description('Required email argument')]
    string $email,
    #[Argument]
    string $username,
){
    line("Email argument is $email and username argument is $username.");
};
