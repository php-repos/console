<?php

use PhpRepos\Console\Attributes\Description;
use PhpRepos\Console\Attributes\ShortOption;
use function PhpRepos\Cli\Output\line;

return function (
    #[ShortOption('t')]
    #[Description('The team option for the command')]
    ?string $team = null
) {
    $team = $team ?: 'default-team';
    line("This is the given team option: " . $team);
};
