<?php

use function PhpRepos\Console\Infra\CLI\assert_error;
use function PhpRepos\Console\Infra\Filesystem\root;
use function PhpRepos\Console\Infra\Strings\assert_equal;
use function PhpRepos\TestRunner\Runner\test;

include_once __DIR__ . '/Helper.php';


function help(string $prompt): string
{
    return shell_exec('php ./console -h ' . $prompt);
}

test(
    title: 'it should show the help when -h option passed',
    case: function () {
        $output = shell_exec('php ./console -h');
        $expected = <<<EOD
Usage: console [-h | --help]
               <command> [<options>] [<args>]

Here you can see a list of available commands:
    accept-excessive-arguments
    default-bool-argument
    default-string-argument
    first
    full-fledged                        This is the full-fledged command
    invalid
    invalid-object
    needs-array-argument
    needs-bool-argument
    needs-email
    needs-email-username-args
    needs-force-option
    needs-ids
    needs-list
    needs-optional-argument
    needs-optional-force-option
    needs-optional-list
    needs-optional-option-with-value
    needs-optional-team
    needs-optional-username
    needs-two-options
    needs-username
    no-type
    second                              This is a description
    subdirectory first
    supports-help

EOD;
        assert_equal($output, $expected);
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
Usage: console [-h | --help]
               <command> [<options>] [<args>]

Here you can see a list of available commands:
    accept-excessive-arguments
    default-bool-argument
    default-string-argument
    first
    full-fledged                        This is the full-fledged command
    invalid
    invalid-object
    needs-array-argument
    needs-bool-argument
    needs-email
    needs-email-username-args
    needs-force-option
    needs-ids
    needs-list
    needs-optional-argument
    needs-optional-force-option
    needs-optional-list
    needs-optional-option-with-value
    needs-optional-team
    needs-optional-username
    needs-two-options
    needs-username
    no-type
    second                              This is a description
    subdirectory first
    supports-help

EOD;

        assert_equal($output, $expected);
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
Usage: console first

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
This command does not accept any options.

EOD;

        assert_equal($output, $expected);
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
Usage: console second

Description:
 This is a description
 You can have multiple lines description
 Contain any example

Arguments:
This command does not accept any arguments.

Options:
This command does not accept any options.

EOD;

        assert_equal($output, $expected);
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
Usage: console needs-email [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  --email <email> The email option for the command

EOD;

        assert_equal($output, $expected);
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
Usage: console needs-username [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  -u, --username <username> The username option for the command

EOD;

        assert_equal($output, $expected);
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
Usage: console needs-optional-username [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  -u, --username [<username>] The username option for the command

EOD;

        assert_equal($output, $expected);
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
Usage: console needs-optional-team [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  -t [<team>] The team option for the command

EOD;

        assert_equal($output, $expected);
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
Usage: console needs-force-option [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  -f, --force The force option for the command

EOD;

        assert_equal($output, $expected);
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
Usage: console needs-ids [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  --ids <ids> The ids array option

EOD;

        assert_equal($output, $expected);
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
Usage: console needs-email-username-args <email> <username>

Description:
No description provided for the command.

Arguments:
  <email>    Required email argument
  <username>

Options:
This command does not accept any options.

EOD;

        assert_equal($output, $expected);
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
Usage: console needs-optional-argument [<name>]

Description:
No description provided for the command.

Arguments:
  [<name>]

Options:
This command does not accept any options.

EOD;

        assert_equal($output, $expected);
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
Usage: console full-fledged [<options>] [<password>] [<roles>]

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

        assert_equal($output, $expected);
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
Usage: console needs-bool-argument <force>

Description:
No description provided for the command.

Arguments:
  <force>

Options:
This command does not accept any options.

EOD;

        assert_equal($output, $expected);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should not show the defined variable for excessive arguments',
    case: function () {
        $output = help('accept-excessive-arguments');
        $expected = <<<EOD
Usage: console accept-excessive-arguments [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  --email <email> The email option for the command

EOD;

        assert_equal($output, $expected);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);
