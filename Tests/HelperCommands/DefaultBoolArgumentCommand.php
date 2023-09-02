<?php

use PhpRepos\Console\Attributes\Argument;
use function PhpRepos\Cli\IO\Write\line;

return function (
    #[Argument]
    ?bool $force = true,
) {
    line('Bool argument passed to command is: ' . ($force ? 'true' : 'false'));
};
