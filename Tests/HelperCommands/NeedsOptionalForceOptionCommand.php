<?php

use PhpRepos\Console\Attributes\Description;
use PhpRepos\Console\Attributes\LongOption;
use PhpRepos\Console\Attributes\ShortOption;
use function PhpRepos\Cli\Output\line;

return function (
    #[LongOption('force')]
    #[ShortOption('f')]
    #[Description('The force option for the command')]
    ?bool $option = null
) {
    $value = $option !== null ? ($option ? 'true' : 'false') : 'optional';
    line("This is the given force option: " . $value);
};
