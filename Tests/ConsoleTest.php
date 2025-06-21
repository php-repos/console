<?php

use function PhpRepos\Cli\Output\assert_error;
use function PhpRepos\Cli\Output\assert_line;
use function PhpRepos\Datatype\Str\assert_equal;
use function PhpRepos\FileManager\Paths\root;
use function PhpRepos\TestRunner\Assertions\assert_true;
use function PhpRepos\TestRunner\Runner\test;

include_once __DIR__ . '/Helper.php';

function run(string $prompt): string
{
    return shell_exec('php ./console ' . $prompt);
}

test(
    title: 'it should check Commands directory by default',
    case: function () {
        $output = shell_exec('php ./console');
        $root = root();
        assert_error("There is no command in {$root}Commands path!", $output);
    }
);

test(
    title: 'it should show an error message when there is no command with the given name',
    case: function () {
        $output = run('not-exists');

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
    title: 'it should show an error message when there is no file with the given suffix',
    case: function () {
        $output = run('command-without-suffix');

        assert_error("Command command-without-suffix not found!", $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show a list of available commands when no command passed',
    case: function () {
        $output = run('');
        $expected = <<<EOD
\e[39mUsage: console [-h | --help]
               <command> [<options>] [<args>]

Here you can see a list of available commands:
\e[39m    accept-excessive-arguments
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
\e[39m    subdirectory first
\e[39m    supports-help

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
    title: 'it should show error message when the command\'s option has an invalid type',
    case: function () {
        $output = run('invalid');

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
    title: 'it should show error message when the command\'s option has an invalid object type',
    case: function () {
        $output = run('invalid-object');

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
        $output = run('no-type');

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
    title: 'it should run the given command',
    case: function () {
        $output = run('first');
        assert_true(str_contains($output, 'The first command\'s output.'), 'First command has not been run!');

        $output = run('second');
        assert_true(str_contains($output, 'The second command\'s output.'), 'Second command has not been run!');

        $output = run('subdirectory first');
        assert_true(str_contains($output, 'The subdirectory first command\'s output.'), 'Subdirectory first command has not been run!');
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command with the given required long option',
    case: function () {
        $output = run('needs-email --email=info@phpkg.com');

        assert_line('This is the given email option: info@phpkg.com', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command with the given required long option with space',
    case: function () {
        $output = run('needs-email --email info@phpkg.com');

        assert_line('This is the given email option: info@phpkg.com', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show error message when required option not passed',
    case: function () {
        $output = run('needs-email');
        $expected = <<<EOD
\e[91mError: Option `email` is required.\e[39m
\e[39mUsage: console needs-email [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  --email <email> The email option for the command

EOD;

        assert_true($expected === $output, 'Wrong error message when required option not passed.' . PHP_EOL . $expected . PHP_EOL . $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command with the given optional long option',
    case: function () {
        $output = run('needs-optional-username --username=JohnDoe');

        assert_line('This is the given username option: JohnDoe', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command with the given optional long option using double quote',
    case: function () {
        $output = run('needs-optional-username --username="John Doe"');

        assert_line('This is the given username option: John Doe', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command with default value when long option is optional and not passed',
    case: function () {
        $output = run('needs-optional-username');

        assert_line('This is the given username option: null', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command with the given short option passed by equal sign',
    case: function () {
        $output = run('needs-optional-team -t=development');

        assert_line('This is the given team option: development', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command with the given short option passed by space',
    case: function () {
        $output = run('needs-optional-team -t marketing');

        assert_line('This is the given team option: marketing', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command with the default value when short option not passed',
    case: function () {
        $output = run('needs-optional-team');

        assert_line('This is the given team option: default-team', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command with either options',
    case: function () {
        $output = run('needs-optional-username --username=JohnDoe');

        assert_line('This is the given username option: JohnDoe', $output);

        $output = run('needs-optional-username -u JohnDoe');

        assert_line('This is the given username option: JohnDoe', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command with later option when there are both, short and long options',
    case: function () {
        $output = run('needs-email --email=info@phpkg.com --email=support@phpkg.com');
        assert_line('This is the given email option: support@phpkg.com', $output);

        $output = run('needs-optional-team -t marketing -t=development');
        assert_line('This is the given team option: development', $output);

        $output = run('needs-optional-username --username=JohnDoe -u JaneDoe');
        assert_line('This is the given username option: JaneDoe', $output);

        $output = run('needs-optional-username -u JaneDoe --username=JohnDoe');
        assert_line('This is the given username option: JohnDoe', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command with the given bool short option',
    case: function () {
        $output = run('needs-force-option -f');

        assert_line('This is the given force option: true', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show error message when option is defined as bool but passed using equal',
    case: function () {
        $output = run('needs-force-option -f --force=false');
        $expected = <<<EOD
\e[91mError: Long option `force` must be boolean and does not accept values.\e[39m
\e[39mUsage: console needs-force-option [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  -f, --force The force option for the command

EOD;

        assert_equal($output, $expected);

        $output = run('needs-force-option -f=any-value');
        $expected = <<<EOD
\e[91mError: Short option `f` must be boolean and does not accept values.\e[39m
\e[39mUsage: console needs-force-option [<options>]

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
    title: 'it should run the command with the false value when it is required and the bool option not passed',
    case: function () {
        $output = run('needs-force-option');

        assert_line('This is the given force option: false', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command with the default value when it is optional and the bool option not passed',
    case: function () {
        $output = run('needs-optional-force-option');

        assert_line('This is the given force option: optional', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command with given option array',
    case: function () {
        $output = run('needs-ids --ids=1 --ids=2 --ids=3');

        assert_line('These are passed ids: 1 and 2 and 3', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show an error when the array option is required',
    case: function () {
        $output = run('needs-ids');
        $expected = <<<EOD
\e[91mError: Option `ids` is required.\e[39m
\e[39mUsage: console needs-ids [<options>]

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
    title: 'it should show an error contain short and long option when the array option is required',
    case: function () {
        $output = run('needs-list');

        $expected = <<<EOD
\e[91mError: Option `l|list` is required.\e[39m
\e[39mUsage: console needs-list [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  -l, --list <list> The list array option

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
    title: 'it should run the command with given option array using short option',
    case: function () {
        $output = run('needs-optional-list -l=1 --list=2 -l=3');

        assert_line('These are passed list: 1 and 2 and 3', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command with null when array option is optional',
    case: function () {
        $output = run('needs-optional-list');

        assert_line('These are passed list: null', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run command using two options',
    case: function () {
        $output = run('needs-two-options --email info@phpkg.com --username=john');

        assert_line('This is the given email option: info@phpkg.com and the given username is john', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run command using two options when options passed in any order',
    case: function () {
        $output = run('needs-two-options --username=john --email info@phpkg.com');

        assert_line('This is the given email option: info@phpkg.com and the given username is john', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command using required option when optional option not passed',
    case: function () {
        $output = run('needs-two-options --email info@phpkg.com');

        assert_line('This is the given email option: info@phpkg.com and the given username is default', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show error message when short option passed without value',
    case: function () {
        $output = run('needs-username -u');

        $expected = <<<EOD
\e[91mError: Option needs value.\e[39m
\e[39mUsage: console needs-username [<options>]

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
    title: 'it should show error message when long option passed without value',
    case: function () {
        $output = run('needs-username --username');

        $expected = <<<EOD
\e[91mError: Option needs value.\e[39m
\e[39mUsage: console needs-username [<options>]

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
    title: 'it should show error message when both option passed but the last one does not have value',
    case: function () {
        $expected = <<<EOD
\e[91mError: Option needs value.\e[39m
\e[39mUsage: console needs-username [<options>]

Description:
No description provided for the command.

Arguments:
This command does not accept any arguments.

Options:
  -u, --username <username> The username option for the command

EOD;

        $output = run('needs-username -u john --username');
        assert_equal($output, $expected);

        $output = run('needs-username --username=john -u');
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
    title: 'it should show error message when extra argument passed',
    case: function () {
        $output = run('needs-email --email=info@phpkg.com extra-argument');

        $expected = <<<EOD
\e[91mError: You passed invalid argument to the command.\e[39m
\e[39mUsage: console needs-email [<options>]

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
    title: 'it should pass excessive arguments to the command when there is a variable defined for accepting them',
    case: function () {
        $output = run('accept-excessive-arguments --email=info@phpkg.com --password=secret john -l -p');

        assert_line('--password=secretjohn-l-p', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run commands with passed arguments when defined in command\'s signature',
    case: function () {
        $output = run('needs-email-username-args info@phpkg.com john');

        assert_line('Email argument is info@phpkg.com and username argument is john.', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show error message when passed arguments are more than command\'s need',
    case: function () {
        $output = run('needs-email-username-args info@phpkg.com john extra');

        $expected = <<<EOD
\e[91mError: You passed invalid argument to the command.\e[39m
\e[39mUsage: console needs-email-username-args <email> <username>

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
    title: 'it should run the command with null when the optional argument not passed',
    case: function () {
        $output = run('needs-optional-argument');

        assert_line('Passed argument is: null', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the command that needs bool argument',
    case: function () {
        $output = run('needs-bool-argument true');
        assert_line('Bool argument passed to command is: true', $output);

        $output = run('needs-bool-argument false');
        assert_line('Bool argument passed to command is: false', $output);

        $output = run('needs-bool-argument any-value');
        $expected = <<<EOD
\e[91mError: Bool argument accepts true or false.\e[39m
\e[39mUsage: console needs-bool-argument <force>

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
    title: 'it should pass all remaining arguments for array argument',
    case: function () {
        $output = run('needs-array-argument --email info@phpkg.com JohnDoe 1 2 3 4 5');

        assert_line('Email: info@phpkg.com, Username: JohnDoe, IDs: 1 and 2 and 3 and 4 and 5', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should run the full-fledged command',
    case: function () {
        $output = run('full-fledged --email=info@phpkg.com -u JohnDoe password -f user customer supplier');

        assert_line('Email: info@phpkg.com, Username: JohnDoe, Password: password, Roles: user, customer, supplier Force: true', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should show error message when required argument not passed',
    case: function () {
        $output = run('needs-email-username-args');

        $expected = <<<EOD
\e[91mError: Argument `email` is required.\e[39m
\e[39mUsage: console needs-email-username-args <email> <username>

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
    title: 'it should pass the default defined value for string arguments',
    case: function () {
        $output = run('default-string-argument');

        assert_line('The environment set as: development', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should pass the default defined value for bool arguments',
    case: function () {
        $output = run('default-bool-argument');

        assert_line('Bool argument passed to command is: true', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should pass default value when value not passed for an option',
    case: function () {
        $output = run('needs-optional-option-with-value');

        assert_line('Username passed as empty string', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should allow commands handle late h',
    case: function () {
        $output = run('supports-help -h');

        assert_line('h is true and help is false', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);

test(
    title: 'it should allow commands handle late h',
    case: function () {
        $output = run('supports-help --help');

        assert_line('h is false and help is true', $output);
    },
    before: function () {
        copy_commands();
    },
    after: function () {
        delete_commands();
    }
);
