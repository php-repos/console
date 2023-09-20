<?php

namespace Tests\ConsoleHelpTest;

use function PhpRepos\Cli\Output\assert_error;
use function PhpRepos\TestRunner\Assertions\Boolean\assert_true;
use function PhpRepos\TestRunner\Runner\test;
use function Tests\Helper\copy_commands;
use function Tests\Helper\delete_commands;


function help(string $prompt): string
{
    return shell_exec('php ./console -h ' . $prompt);
}

test(
    title: 'it should show console help when there is no command on the given directory',
    case: function () {
        $output = shell_exec('php ./console -h');

        $help_expected_output = <<<EOD
\e[39mUsage: console [-h | --help]
               <command> [<options>] [<args>]

EOD;

        assert_true($help_expected_output === $output, 'Expected: ' . PHP_EOL . $help_expected_output . PHP_EOL . 'Actual output is:' . PHP_EOL . $output . PHP_EOL);
    }
);

test(
    title: 'it should show the help when -h option passed',
    case: function () {
        $output = shell_exec('php ./console -h');
        $expected = <<<EOD
\e[39mUsage: console [-h | --help]
               <command> [<options>] [<args>]

Here you can see a list of available commands:
\e[39m    default-bool-argument
\e[39m    default-string-argument
\e[39m    first
\e[39m    full-fledged                        This is the full-fledged command
\e[39m    invalid
\e[39m    invalid-object
\e[39m    needs-array-argument
\e[39m    needs-bool-argument
\e[39m    needs-email
\e[39m    needs-email-username-args
\e[39m    needs-force-option
\e[39m    needs-ids
\e[39m    needs-list
\e[39m    needs-optional-argument
\e[39m    needs-optional-force-option
\e[39m    needs-optional-list
\e[39m    needs-optional-option-with-value
\e[39m    needs-optional-team
\e[39m    needs-optional-username
\e[39m    needs-two-options
\e[39m    needs-username
\e[39m    no-type
\e[39m    second                              This is a description
\e[39m    subdirectory/first

EOD;
        assert_true($expected === $output, 'Expected: ' . PHP_EOL . $expected . PHP_EOL . 'Actual output is:' . PHP_EOL . $output . PHP_EOL);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show the help when --help option passed',
    case: function () {
        $output = shell_exec('php ./console --help');
        $expected = <<<EOD
\e[39mUsage: console [-h | --help]
               <command> [<options>] [<args>]

Here you can see a list of available commands:
\e[39m    default-bool-argument
\e[39m    default-string-argument
\e[39m    first
\e[39m    full-fledged                        This is the full-fledged command
\e[39m    invalid
\e[39m    invalid-object
\e[39m    needs-array-argument
\e[39m    needs-bool-argument
\e[39m    needs-email
\e[39m    needs-email-username-args
\e[39m    needs-force-option
\e[39m    needs-ids
\e[39m    needs-list
\e[39m    needs-optional-argument
\e[39m    needs-optional-force-option
\e[39m    needs-optional-list
\e[39m    needs-optional-option-with-value
\e[39m    needs-optional-team
\e[39m    needs-optional-username
\e[39m    needs-two-options
\e[39m    needs-username
\e[39m    no-type
\e[39m    second                              This is a description
\e[39m    subdirectory/first

EOD;

        assert_true($expected === $output, 'Expected: ' . PHP_EOL . $expected . PHP_EOL . 'Actual output is:' . PHP_EOL . $output . PHP_EOL);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show an error message when there is no command with the given name',
    case: function () {
        $output = help('not-exists');

        assert_error("Command not-exists not found!", $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show the help for a simple command',
    case: function () {
        $output = help('first');
        $expected = <<<EOD
\e[39mUsage: console first

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
This command does not accept any options.

EOD;

        assert_true($expected === $output, 'First command\'s help did not get shown!' . PHP_EOL . $expected . PHP_EOL . $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show the help for a command by given description in the docblock',
    case: function () {
        $output = help('second');
        $expected = <<<EOD
\e[39mUsage: console second

Description:
 This is a description
 You can have multiple lines description
 Contain any example

Arguments:
This command does not accept any arguments.

Options:
This command does not accept any options.

EOD;

        assert_true($expected === $output, 'Second command\'s help did not get shown!' . PHP_EOL . $expected . PHP_EOL . $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show the help for a command that requires option',
    case: function () {
        $output = help('needs-email');
        $expected = <<<EOD
\e[39mUsage: console needs-email [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  --email <email> The email option for the command

EOD;

        assert_true($expected === $output, 'needs-email command\'s help did not get shown!' . PHP_EOL . $expected . PHP_EOL . $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show the help for a command that requires option with long and short option',
    case: function () {
        $output = help('needs-username');
        $expected = <<<EOD
\e[39mUsage: console needs-username [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  -u, --username <username> The username option for the command

EOD;

        assert_true($expected === $output, 'needs-username command\'s help did not get shown!' . PHP_EOL . $expected . PHP_EOL . $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show the help for a command that has optional option with long and short option',
    case: function () {
        $output = help('needs-optional-username');
        $expected = <<<EOD
\e[39mUsage: console needs-optional-username [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  -u, --username [<username>] The username option for the command

EOD;

        assert_true($expected === $output, 'needs-optional-username command\'s help did not get shown!' . PHP_EOL . $expected . PHP_EOL . $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show the help for a command that has optional option with short option',
    case: function () {
        $output = help('needs-optional-team');
        $expected = <<<EOD
\e[39mUsage: console needs-optional-team [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  -t [<team>] The team option for the command

EOD;

        assert_true($expected === $output, 'needs-optional-team command\'s help did not get shown!' . PHP_EOL . $expected . PHP_EOL . $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show the help for a command that needs bool option',
    case: function () {
        $output = help('needs-force-option');
        $expected = <<<EOD
\e[39mUsage: console needs-force-option [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  -f, --force The force option for the command

EOD;

        assert_true($expected === $output, 'needs-force-option command\'s help did not get shown!' . PHP_EOL . $expected . PHP_EOL . $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show the help for a command that needs array option',
    case: function () {
        $output = help('needs-ids');
        $expected = <<<EOD
\e[39mUsage: console needs-ids [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  --ids <ids> The ids array option

EOD;

        assert_true($expected === $output, 'needs-ids command\'s help did not get shown!' . PHP_EOL . $expected . PHP_EOL . $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show the help for a command that needs required arguments',
    case: function () {
        $output = help('needs-email-username-args');
        $expected = <<<EOD
\e[39mUsage: console needs-email-username-args <email> <username>

Description:
No description provided for the command.

Arguments:
  <email>    Required email argument
  <username>

Options:
This command does not accept any options.

EOD;

        assert_true($expected === $output, 'needs-email-username-args command\'s help did not get shown!' . PHP_EOL . $expected . PHP_EOL . $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show error message on help when the command\'s option has an invalid type',
    case: function () {
        $output = help('invalid');

        assert_error('Error: No option or argument has been defined.', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show error message on help when the command\'s option has an invalid object type',
    case: function () {
        $output = help('invalid-object');

        assert_error('Error: Command options must be builtin type (bool, string, int, array).', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show error message when command input does not have type',
    case: function () {
        $output = help('no-type');

        assert_error('Error: Command\'s parameter must have type.', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show document for optional argument',
    case: function () {
        $output = help('needs-optional-argument');
        $expected = <<<EOD
\e[39mUsage: console needs-optional-argument [<name>]

Description:
No description provided for the command.

Arguments:
  [<name>]

Options:
This command does not accept any options.

EOD;

        assert_true($expected === $output, 'needs-optional-argument command\'s help did not get shown!' . PHP_EOL . $expected . PHP_EOL . $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show document for full-fledged command',
    case: function () {
        $output = help('full-fledged');
        $expected = <<<EOD
\e[39mUsage: console full-fledged [<options>] [<password>] [<roles>]

Description:
 This is the full-fledged command
 It uses both options and arguments
 Example: console full-fledged --email=info@phpkg.com -u JohnDoe password -f user customer supplier

Arguments:
  [<password>] The password to be passed using option or argument
  [<roles>]    List of rules for user

Options:
  --email <email>       The required email option
  -u <username>         The required username option
  --password <password> The password to be passed using option or argument
  -f, --force           Optional force option

EOD;

        assert_true($expected === $output, 'full-fledged command\'s help did not get shown!' . PHP_EOL . $expected . PHP_EOL . $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show document for a command that needs only arguments',
    case: function () {
        $output = help('needs-bool-argument');
        $expected = <<<EOD
\e[39mUsage: console needs-bool-argument <force>

Description:
No description provided for the command.

Arguments:
  <force>

Options:
This command does not accept any options.

EOD;

        assert_true($expected === $output, 'needs-bool-argument command\'s help did not get shown!' . PHP_EOL . $expected . PHP_EOL . $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);
