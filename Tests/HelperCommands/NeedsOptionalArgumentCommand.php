<?php

use PhpRepos\Console\Attributes\Argument;
use function PhpRepos\Cli\Output\line;

return function (
    #[Argument]
    ?string $name = null,
) {
    line('Passed argument is: ' . ($name ?: 'null'));
};
