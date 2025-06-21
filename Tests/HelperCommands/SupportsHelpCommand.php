<?php

use PhpRepos\Console\Attributes\LongOption;
use PhpRepos\Console\Attributes\ShortOption;
use function PhpRepos\Cli\Output\line;

return function (
    #[ShortOption('h')]
    bool $h,
    #[LongOption('help')]
    bool $help
) {
    $h = $h ? 'true' : 'false';
    $help = $help ? 'true' : 'false';
    line("h is $h and help is $help");
};
