# Console Package

A powerful, flexible command-line interface framework for PHP applications.

## Table of Contents

- [Installation](#installation)
- [Creating Your First Command](#creating-your-first-command)
- [Adding Command Description](#adding-command-description)
- [Working with Arguments](#working-with-arguments)
- [Working with Options](#working-with-options)
- [Parameter Descriptions](#parameter-descriptions)
- [Exit Codes](#exit-codes)
- [Subcommands](#subcommands)
- [Advanced Customization](#advanced-customization)
- [For Developers](#for-developers)

## Installation

Install the Console Package using `phpkg`:

```bash
phpkg add console
```

After installation, build your project:

```bash
phpkg build
```

Your console commands will be available in the `build` directory.

## Creating Your First Command

Commands are created as PHP files that return a callable (function or closure). Let's start with the simplest possible command.

### Step 1: Create the Command File

Create a file at `Commands/GreetCommand.php`:

```php
<?php

return function () {
    echo "Hello, World!\n";
};
```

### Step 2: Build and Run

```bash
phpkg build
cd build
php console greet
```

Output:
```
Hello, World!
```

That's it! The Console Package automatically:
- Discovers your command file
- Converts the filename `GreetCommand.php` to command name `greet`
- Makes it available via the console

### Viewing Available Commands

To see all available commands:

```bash
php console --help
```

Or simply:

```bash
php console
```

## Adding Command Description

Add documentation to your command using a docblock comment:

```php
<?php

/**
 * Greets the user with a friendly message.
 */
return function () {
    echo "Hello, World!\n";
};
```

Now when you run `php console greet --help`, you'll see:

```
Usage: console greet

Description:
 Greets the user with a friendly message.

Arguments:
This command does not accept any arguments.

Options:
This command does not accept any options.
```

You can add multi-line descriptions:

```php
<?php

/**
 * Greets the user with a friendly message.
 *
 * This command demonstrates how to create a simple
 * console command with proper documentation.
 */
return function () {
    echo "Hello, World!\n";
};
```

## Working with Arguments

Arguments are positional parameters passed to your command. They appear after the command name.

### Adding a Single Argument

Use the `Argument` attribute to accept arguments:

```php
<?php

use PhpRepos\Console\Business\Attributes\Argument;

/**
 * Greets a person by name.
 */
return function (
    #[Argument]
    string $name,
) {
    echo "Hello, $name!\n";
};
```

Usage:
```bash
php console greet John
# Output: Hello, John!
```

### Multiple Arguments

Arguments are passed in the order they're defined:

```php
<?php

use PhpRepos\Console\Business\Attributes\Argument;

/**
 * Greets a person with their title and name.
 */
return function (
    #[Argument]
    string $title,
    #[Argument]
    string $name,
) {
    echo "Hello, $title $name!\n";
};
```

Usage:
```bash
php console greet Dr. Smith
# Output: Hello, Dr. Smith!
```

### Optional Arguments

Make arguments optional using nullable types or default values:

```php
<?php

use PhpRepos\Console\Business\Attributes\Argument;

/**
 * Greets a person, or everyone if no name provided.
 */
return function (
    #[Argument]
    ?string $name = null,
) {
    if ($name === null) {
        echo "Hello, everyone!\n";
    } else {
        echo "Hello, $name!\n";
    }
};
```

Usage:
```bash
php console greet
# Output: Hello, everyone!

php console greet John
# Output: Hello, John!
```

### Array Arguments

Accept multiple values for an argument:

```php
<?php

use PhpRepos\Console\Business\Attributes\Argument;

/**
 * Greets multiple people.
 */
return function (
    #[Argument]
    array $names = [],
) {
    foreach ($names as $name) {
        echo "Hello, $name!\n";
    }
};
```

Usage:
```bash
php console greet John Jane Bob
# Output:
# Hello, John!
# Hello, Jane!
# Hello, Bob!
```

## Working with Options

Options are named parameters that can be passed in any order using `--name` (long option) or `-n` (short option) syntax.

### Long Options

Use the `LongOption` attribute to define options with the `--name` syntax:

```php
<?php

use PhpRepos\Console\Business\Attributes\LongOption;

/**
 * Greets a user by email address.
 */
return function (
    #[LongOption('email')]
    string $email,
) {
    echo "Hello, $email!\n";
};
```

Usage:
```bash
php console greet --email=john@example.com
# Output: Hello, john@example.com!

# Also works with space:
php console greet --email john@example.com
# Output: Hello, john@example.com!
```

### Short Options

Use the `ShortOption` attribute for single-letter options with the `-n` syntax:

```php
<?php

use PhpRepos\Console\Business\Attributes\ShortOption;

/**
 * Greets a user.
 */
return function (
    #[ShortOption('n')]
    string $name,
) {
    echo "Hello, $name!\n";
};
```

Usage:
```bash
php console greet -n John
# Output: Hello, John!

# Also works with equals:
php console greet -n=John
# Output: Hello, John!
```

### Combining Long and Short Options

Provide both long and short versions of the same option:

```php
<?php

use PhpRepos\Console\Business\Attributes\LongOption;
use PhpRepos\Console\Business\Attributes\ShortOption;

/**
 * Greets a user.
 */
return function (
    #[LongOption('name'), ShortOption('n')]
    string $name,
) {
    echo "Hello, $name!\n";
};
```

Usage:
```bash
php console greet --name John
php console greet -n John
# Both output: Hello, John!
```

### Boolean Options (Flags)

Options with `bool` type work as flags:

```php
<?php

use PhpRepos\Console\Business\Attributes\LongOption;
use PhpRepos\Console\Business\Attributes\ShortOption;

/**
 * Greets a user with optional shouting.
 */
return function (
    #[LongOption('loud'), ShortOption('l')]
    bool $loud,
    #[LongOption('name'), ShortOption('n')]
    string $name,
) {
    $greeting = "Hello, $name!";
    echo $loud ? strtoupper($greeting) . "\n" : $greeting . "\n";
};
```

Usage:
```bash
php console greet --name John
# Output: Hello, John!

php console greet --name John --loud
# Output: HELLO, JOHN!

php console greet -n John -l
# Output: HELLO, JOHN!
```

### Optional Options

Make options optional with default values:

```php
<?php

use PhpRepos\Console\Business\Attributes\LongOption;

/**
 * Greets a user with customizable greeting.
 */
return function (
    #[LongOption('name')]
    string $name,
    #[LongOption('greeting')]
    string $greeting = 'Hello',
) {
    echo "$greeting, $name!\n";
};
```

Usage:
```bash
php console greet --name John
# Output: Hello, John!

php console greet --name John --greeting Hi
# Output: Hi, John!
```

### Mixing Arguments and Options

You can combine arguments and options in the same command:

```php
<?php

use PhpRepos\Console\Business\Attributes\Argument;
use PhpRepos\Console\Business\Attributes\LongOption;
use PhpRepos\Console\Business\Attributes\ShortOption;

/**
 * Creates a user account.
 */
return function (
    #[Argument]
    string $username,
    #[LongOption('email'), ShortOption('e')]
    string $email,
    #[LongOption('admin'), ShortOption('a')]
    bool $admin = false,
) {
    echo "Creating user: $username\n";
    echo "Email: $email\n";
    echo "Admin: " . ($admin ? 'Yes' : 'No') . "\n";
};
```

Usage:
```bash
php console create-user john --email john@example.com
# Output:
# Creating user: john
# Email: john@example.com
# Admin: No

php console create-user jane -e jane@example.com --admin
# Output:
# Creating user: jane
# Email: jane@example.com
# Admin: Yes
```

## Parameter Descriptions

Add descriptions to parameters using the `Description` attribute:

```php
<?php

use PhpRepos\Console\Business\Attributes\Argument;
use PhpRepos\Console\Business\Attributes\Description;
use PhpRepos\Console\Business\Attributes\LongOption;
use PhpRepos\Console\Business\Attributes\ShortOption;

/**
 * Creates a new user account in the system.
 */
return function (
    #[Argument, Description('The username for the new account')]
    string $username,
    #[LongOption('email'), ShortOption('e'), Description('User email address')]
    string $email,
    #[LongOption('password'), ShortOption('p'), Description('Account password')]
    string $password,
    #[LongOption('admin'), ShortOption('a'), Description('Grant administrator privileges')]
    bool $admin = false,
) {
    echo "Creating user: $username\n";
    // ... user creation logic
};
```

Now `php console create-user --help` shows:

```
Usage: console create-user [<options>] <username>

Description:
 Creates a new user account in the system.

Arguments:
  <username> The username for the new account

Options:
  -e, --email <email>       User email address
  -p, --password <password> Account password
  -a, --admin               Grant administrator privileges
```

## Exit Codes

Return an integer from your command to set the exit code:

```php
<?php

use PhpRepos\Console\Business\Attributes\LongOption;

/**
 * Connects to a server.
 */
return function (
    #[LongOption('host')]
    string $host,
) {
    echo "Connecting to $host...\n";

    // Simulate connection
    if ($host === 'localhost') {
        echo "Connected successfully!\n";
        return 0; // Success
    } else {
        echo "Connection failed!\n";
        return 1; // Error
    }
};
```

Usage:
```bash
php console connect --host localhost
echo $?  # Outputs: 0 (success)

php console connect --host invalid
echo $?  # Outputs: 1 (error)
```

**Exit Code Conventions:**
- `0` - Success
- `1` - General error
- `2` - Misuse of command
- `126` - Command cannot execute
- `127` - Command not found
- `130` - Terminated by Ctrl+C
- Custom codes - Define your own for specific errors

## Subcommands

Organize related commands using directory structure. The directory name becomes part of the command name.

### Creating Subcommands

**File:** `Commands/Users/CreateCommand.php`
```php
<?php

use PhpRepos\Console\Business\Attributes\Argument;
use PhpRepos\Console\Business\Attributes\Description;

/**
 * Creates a new user.
 */
return function (
    #[Argument, Description('Username for the new user')]
    string $username,
) {
    echo "Creating user: $username\n";
};
```

**File:** `Commands/Users/DeleteCommand.php`
```php
<?php

use PhpRepos\Console\Business\Attributes\Argument;
use PhpRepos\Console\Business\Attributes\Description;

/**
 * Deletes an existing user.
 */
return function (
    #[Argument, Description('Username to delete')]
    string $username,
) {
    echo "Deleting user: $username\n";
};
```

**File:** `Commands/Users/ListCommand.php`
```php
<?php

/**
 * Lists all users.
 */
return function () {
    echo "Listing all users...\n";
};
```

Usage:
```bash
php console users create john
php console users delete john
php console users list
```

Help output:
```bash
php console --help
# Shows:
#   users create    Creates a new user
#   users delete    Deletes an existing user
#   users list      Lists all users
```

### Nested Subcommands

You can nest commands deeper:

**File:** `Commands/Database/Users/ImportCommand.php`
```php
<?php

/**
 * Imports users from a file.
 */
return function () {
    echo "Importing users...\n";
};
```

Usage:
```bash
php console database users import
```

## Advanced Customization

### Customizing the Console File

The default `console` file handles command discovery and execution. You can customize it to change behavior, add custom commands, or modify help output.

**Default console file structure:**
```php
#!/usr/bin/env php
<?php

use PhpRepos\Console\Business\Finder;
use PhpRepos\Console\Business\Command;
use PhpRepos\Console\UI;
use PhpRepos\Console\Infra\CLI;

global $argv;

$entrypoint = $_SERVER['SCRIPT_FILENAME'];
$short_options = 'h';
$long_options = ['help'];
$options = getopt($short_options, $long_options, $offset);
$wants_help = isset($options['h']) || isset($options['help']);
$inputs = array_slice($argv, $offset);

// Discover commands
$finder_outcome = Finder\path('Commands');

if (!$finder_outcome->success) {
    CLI\error($finder_outcome->message);
    exit(1);
}

$command_handlers = $finder_outcome->data['handlers'];

// Find command
$find_outcome = Command\find($command_handlers, $inputs);

if (!$find_outcome->success) {
    CLI\error($find_outcome->message);

    $all_outcome = Command\all($command_handlers);
    $output = UI\short_help($all_outcome->data['commands'], $entrypoint);
    CLI\write($output);
    exit(1);
}

// Show help if requested
if ($wants_help) {
    $describe_outcome = Command\describe($find_outcome->data['handler']);

    if (!$describe_outcome->success) {
        CLI\error($describe_outcome->message);
        exit(1);
    }

    $output = UI\long_help(
        $describe_outcome->data,
        $entrypoint,
        $find_outcome->data['name']
    );
    CLI\write($output);
    exit(0);
}

// Run command
$run_outcome = Command\run(
    $find_outcome->data['name'],
    $find_outcome->data['handler'],
    $command_handlers,
    $inputs
);

if (!$run_outcome->success) {
    CLI\error($run_outcome->message);
    exit($run_outcome->data['exit_code'] ?? 1);
}

exit($run_outcome->data['exit_code'] ?? 0);
```

### Example: Custom Command Directory

Change where commands are loaded from:

```php
// Instead of:
$finder_outcome = Finder\path('Commands');

// Use:
$finder_outcome = Finder\path('MyCommands', 'Handler.php');
```

This will:
- Look in `MyCommands/` directory instead of `Commands/`
- Look for files ending in `Handler.php` instead of `Command.php`

### Example: Adding Programmatic Commands

Add commands without creating files:

```php
$finder_outcome = Finder\path('Commands');
$command_handlers = $finder_outcome->data['handlers'];

// Add custom command
$command_handlers['version'] = function () {
    echo "MyApp version 1.0.0\n";
    return 0;
};

$command_handlers['env'] = function () {
    echo "Environment: " . getenv('APP_ENV') . "\n";
    return 0;
};

// Continue with normal flow...
$find_outcome = Command\find($command_handlers, $inputs);
```

### Example: Custom Help Output

Replace the help formatting functions:

```php
// Custom short help
function my_short_help(array $commands, string $entrypoint): string
{
    $output = "🚀 MyApp CLI Tool\n\n";
    $output .= "Usage: $entrypoint <command> [options]\n\n";
    $output .= "Available Commands:\n";

    foreach ($commands as $cmd) {
        $output .= sprintf("  %-20s %s\n", $cmd['name'], $cmd['description']);
    }

    return $output;
}

// Use custom formatter
if (!$find_outcome->success) {
    CLI\error($find_outcome->message);

    $all_outcome = Command\all($command_handlers);
    $output = my_short_help($all_outcome->data['commands'], $entrypoint);
    CLI\write($output);
    exit(1);
}
```

### Example: Additional Help Options

Add more help flags:

```php
$short_options = 'hv';  // Add 'v' for version
$long_options = ['help', 'version'];
$options = getopt($short_options, $long_options, $offset);

$wants_help = isset($options['h']) || isset($options['help']);
$wants_version = isset($options['v']) || isset($options['version']);

if ($wants_version) {
    echo "MyApp version 1.0.0\n";
    exit(0);
}
```

### Example: Logging Command Execution

Add logging using the Observer pattern:

```php
use PhpRepos\Console\Business\Signals\RunningConsoleCommand;
use PhpRepos\Console\Business\Signals\CommandExecutionCompleted;
use function PhpRepos\Observer\API\Bus\observe;

observe(
    RunningConsoleCommand::class,
    function ($signal) {
        file_put_contents(
            'console.log',
            "[" . date('Y-m-d H:i:s') . "] Running: {$signal->command_name}\n",
            FILE_APPEND
        );
    }
);

observe(
    CommandExecutionCompleted::class,
    function ($signal) {
        file_put_contents(
            'console.log',
            "[" . date('Y-m-d H:i:s') . "] Completed: {$signal->command_name} (exit: {$signal->exit_code})\n",
            FILE_APPEND
        );
    }
);
```

## For Developers

This section is for developers who want to understand the internal architecture and create custom integrations.

### Architecture Overview

The Console Package uses **Natural Architecture** with three distinct layers:

#### 1. Business Layer (`Source/Business/`)

Defines **what** the system does through pure specifications. Functions return `Outcome` objects and delegate implementation to the Solution layer.

**Key Components:**
- `Command\find()` - Find command from inputs
- `Command\all()` - Get all commands
- `Command\describe()` - Get command details
- `Command\run()` - Execute command
- `Finder\path()` - Discover commands from filesystem

**Outcome Pattern:**
```php
class Outcome {
    public bool $success;      // Did operation succeed?
    public string $message;    // Error or status message
    public array $data;        // Result data
}
```

#### 2. Solution Layer (`Source/Solution/`)

Contains **how** things are implemented - the actual logic for parsing, reflection, and processing.

**Key Components:**
- `Handlers\command()` - Extract command name
- `Handlers\get_description()` - Get full description
- `Handlers\get_arguments()` - Get argument definitions
- `Handlers\get_options()` - Get option definitions
- `Inputs\command_index()` - Find command position
- `Execution\execute()` - Execute with parameters

#### 3. Infrastructure Layer (`Source/Infra/` and `Source/UI.php`)

Handles I/O, formatting, and system-level concerns.

**Key Components:**
- `UI\short_help()` - Format command list
- `UI\long_help()` - Format detailed help
- `CLI\write()` - Output text
- `CLI\error()` - Output errors
- `CLI\line()` - Output with color

### API Reference

#### Business Functions

**`Finder\path(string $relative_path, string $suffix = 'Command.php'): Outcome`**

Discovers commands from a directory.

Returns:
```php
[
    'success' => true,
    'message' => 'Commands found successfully',
    'data' => [
        'handlers' => [
            'command-name' => callable,
            // ...
        ]
    ]
]
```

**`Command\find(array $command_handlers, array $inputs): Outcome`**

Finds the best matching command.

Returns:
```php
[
    'success' => true,
    'message' => '',
    'data' => [
        'name' => 'command-name',
        'handler' => callable
    ]
]
```

**`Command\all(array $command_handlers): Outcome`**

Gets all commands with descriptions.

Returns:
```php
[
    'success' => true,
    'message' => '',
    'data' => [
        'commands' => [
            ['name' => 'cmd1', 'description' => 'Desc 1'],
            ['name' => 'cmd2', 'description' => 'Desc 2'],
        ]
    ]
]
```

**`Command\describe(callable $command): Outcome`**

Gets detailed command information.

Returns:
```php
[
    'success' => true,
    'message' => '',
    'data' => [
        'description' => 'Full command description',
        'arguments' => [
            ['name' => 'arg1', 'required' => true, 'description' => 'Arg desc'],
        ],
        'options' => [
            ['short' => 'o', 'long' => 'option', 'name' => 'option',
             'type' => 'string', 'required' => true, 'description' => 'Opt desc'],
        ]
    ]
]
```

**`Command\run(string $name, callable $handler, array $handlers, array $inputs): Outcome`**

Executes a command.

Returns:
```php
[
    'success' => true,
    'message' => '',
    'data' => [
        'exit_code' => 0
    ]
]
```

#### UI Functions

**`UI\short_help(array $commands, string $entrypoint): string`**

Formats a list of commands.

Input `$commands`:
```php
[
    ['name' => 'create', 'description' => 'Creates something'],
    ['name' => 'delete', 'description' => ''],
]
```

Returns formatted help text.

**`UI\long_help(array $command_data, string $entrypoint, string $command_name): string`**

Formats detailed command help.

Input `$command_data`:
```php
[
    'description' => 'Command description',
    'arguments' => [
        ['name' => 'file', 'required' => true, 'description' => 'File path'],
    ],
    'options' => [
        ['short' => 'f', 'long' => 'force', 'name' => 'force',
         'type' => 'bool', 'required' => false, 'description' => 'Force operation'],
    ]
]
```

Returns formatted help text.

### Creating Custom Integrations

#### Example: Web Interface

```php
use PhpRepos\Console\Business\Command;

// Get all commands
$all_outcome = Command\all($command_handlers);
$commands = $all_outcome->data['commands'];

// Display in HTML
foreach ($commands as $cmd) {
    echo "<div class='command'>";
    echo "<h3>{$cmd['name']}</h3>";
    echo "<p>{$cmd['description']}</p>";
    echo "</div>";
}

// Execute from web request
$inputs = explode(' ', $_POST['command']);
$find_outcome = Command\find($command_handlers, $inputs);

if ($find_outcome->success) {
    $run_outcome = Command\run(
        $find_outcome->data['name'],
        $find_outcome->data['handler'],
        $command_handlers,
        $inputs
    );

    echo "Exit code: " . $run_outcome->data['exit_code'];
}
```

#### Example: Custom Help Formatter

```php
function json_help(array $commands, string $entrypoint): string
{
    return json_encode([
        'entrypoint' => $entrypoint,
        'commands' => $commands
    ], JSON_PRETTY_PRINT);
}

// Use it
$all_outcome = Command\all($command_handlers);
$json = json_help($all_outcome->data['commands'], 'console');
file_put_contents('commands.json', $json);
```

### Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development guidelines.

### Upgrading

See [UPGRADE.md](UPGRADE.md) for migration instructions from previous versions.

## License

This package is open-source software licensed under the MIT license.
