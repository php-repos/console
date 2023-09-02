<?php

use PhpRepos\Console\Attributes\Description;
use PhpRepos\Console\Attributes\LongOption;
use PhpRepos\Console\Attributes\ShortOption;
use function PhpRepos\Cli\IO\Write\line;

return function (
    #[LongOption('force')]
    #[ShortOption('f')]
    #[Description('The force option for the command')]
    bool $option
) {
    line("This is the given force option: " . ($option ? 'true' : 'false'));
};
