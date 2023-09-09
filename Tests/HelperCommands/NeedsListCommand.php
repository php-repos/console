<?php

use PhpRepos\Console\Attributes\Description;
use PhpRepos\Console\Attributes\LongOption;
use PhpRepos\Console\Attributes\ShortOption;
use function PhpRepos\Cli\IO\Write\line;

return function (
    #[LongOption('list')]
    #[ShortOption('l')]
    #[Description('The list array option')]
    array $list
) {
    line("These are passed list: " . implode(' and ', $list));
};
