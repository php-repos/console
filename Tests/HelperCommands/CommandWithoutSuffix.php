<?php

use function PhpRepos\Cli\IO\Write\error;

return function () {
    error('This should not run!');
};
