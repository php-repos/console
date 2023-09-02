<?php

use PhpRepos\Console\Attributes\Argument;
use function PhpRepos\Cli\IO\Write\line;

return function (
    #[Argument]
    ?string $environment = 'development',
) {
    line("The environment set as: $environment");
};
