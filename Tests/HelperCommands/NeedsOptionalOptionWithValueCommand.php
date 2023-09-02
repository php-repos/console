<?php

use PhpRepos\Console\Attributes\LongOption;
use function PhpRepos\Cli\IO\Write\line;

return function (
    #[LongOption('username')]
    string $username = '',
) {
    $username = $username === '' ? 'empty string' : $username;

    line('Username passed as ' . $username);
};
