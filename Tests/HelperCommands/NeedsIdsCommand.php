<?php

use PhpRepos\Console\Attributes\Description;
use PhpRepos\Console\Attributes\LongOption;
use function PhpRepos\Cli\Output\line;

return function (
    #[LongOption('ids')]
    #[Description('The ids array option')]
    array $ids
) {
    line("These are passed ids: " . implode(' and ', $ids));
};
