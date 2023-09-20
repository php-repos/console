<?php

use function PhpRepos\Cli\Output\error;

return function () {
    error('This should not run!');
};
