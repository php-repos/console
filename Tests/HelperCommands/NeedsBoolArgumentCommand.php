<?php

use PhpRepos\Console\Attributes\Argument;
use function PhpRepos\Cli\Output\line;

return function (
    #[Argument]
    bool $force,
) {
    line('Bool argument passed to command is: ' . ($force ? 'true' : 'false'));
};
