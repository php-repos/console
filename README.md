# Console Package

## Introduction

The Console Package simplifies creating and running CLI commands for PHP applications using `phpkg`. It supports both standalone commands and nested subcommands, making it ideal for building lightweight CLI tools or extensible applications.

---

## Installation

To use the Console Package, install it as a dependency with `phpkg`:

```bash
phpkg add https://github.com/php-repos/console.git
```

## Running Commands

After installation, build your project with `phpkg build` to access the Console Package’s CLI functionality. You can run commands using either of these methods from the `builds/development` directory:

1. Using `php console`

   ```bash
   php console make-user --email=johndoe@example.com johndoe -p=secret
   ```

2. Using `./console`

   ```bash
   ./console make-user --email=johndoe@example.com johndoe -p=secret
   ```

Both methods provide identical access to the Console Package’s CLI commands—choose the one that fits your workflow.

## Creating Commands

Create commands by adding files under the `Source/Commands` directory. Use PascalCase filenames matching the command name (e.g., `MakeUserCommand.php` for `make-user`).

**Subcommands**

Subcommands are supported via directory structure. For example, to create a `users create` subcommand, add a file at `Source/Commands/Users/CreateCommand.php`. The Console Package maps directory paths to command names using space as a separator.

### Defining Command Options

Use attributes to define options in your command file. Here’s an example:

```php
use PhpRepos\Console\Attributes\LongOption;
use PhpRepos\Console\Attributes\ShortOption;

return function (
    #[LongOption('email')]
    string $email,
    #[ShortOption('p')]
    string $password,
    #[LongOption('force'), ShortOption('f')]
    ?bool $force = false,
) {
    // Command logic here
};
```

Long options (e.g., `--email`) and short options (e.g., `-p`) support PHP types (`string`, `bool`, etc.).
Optional parameters can use nullable types (e.g., `?bool`) or default values.

### Defining Command Arguments

Add the Argument attribute to accept command-line arguments:

```php
use PhpRepos\Console\Attributes\Argument;
use PhpRepos\Console\Attributes\LongOption;
use PhpRepos\Console\Attributes\ShortOption;

return function (
    #[LongOption('email')]
    string $email,
    #[Argument]
    string $username,
    #[ShortOption('p')]
    string $password,
    #[LongOption('force'), ShortOption('f')]
    ?bool $force = false,
) {
    // Command logic here
};
```

Arguments are positional and mandatory unless marked nullable (e.g., ?string) or given a default value.

### Adding Command Help

Document your command with a docblock for general help:

```php
/**
 * Creates a user with the given email and password.
 * Use the force option to override existing users.
 */
return function (
    #[LongOption('email')]
    string $email,
    #[Argument]
    string $username,
    #[ShortOption('p')]
    string $password,
    #[LongOption('force'), ShortOption('f')]
    ?bool $force = false,
) {
    // Command logic here
};
```

View help with:

```bash
./console --help  # Lists all commands
./console make-user --help  # Shows make-user details
```

### Documenting Parameters

Add Description attributes for detailed argument and option help:

```php
use PhpRepos\Console\Attributes\Argument;
use PhpRepos\Console\Attributes\Description;
use PhpRepos\Console\Attributes\LongOption;
use PhpRepos\Console\Attributes\ShortOption;

/**
 * Creates a user with the given email and password.
 * Use the force option to override existing users.
 */
return function (
    #[LongOption('email'), Description('The user email for the new user')]
    string $email,
    #[Argument, Description('The username for the user')]
    string $username,
    #[ShortOption('p'), Description('The password for user login')]
    string $password,
    #[LongOption('force'), ShortOption('f'), Description('Force user creation')]
    ?bool $force = false,
) {
    // Command logic here
};
```

Running `./console make-user --help` outputs:

```shell
Usage: console make-user [<options>] <username>

Description:
 Creates a user with the given email and password.
 Use the force option to override existing users.

Arguments:
  <username> The username for the user

Options:
  --email <email> The user email for the new user
  -p <password>   The password for user login
  -f, --force     Force user creation
```
## Examples

- File Operation Command
   
   ```php
   /**
    * Deletes a file from the specified path.
    */
   return function (
       #[Argument, Description('The path to the file to delete')]
       string $path,
       #[ShortOption('f'), Description('Force deletion without confirmation')]
       ?bool $force = false,
   ) {
       if ($force || confirm('Are you sure?')) {
           unlink($path);
       }
   };
   ```

- Array Option Example

   ```php
   /**
    * Processes a list of IDs.
    */
   return function (
       #[LongOption('ids'), Description('Comma-separated list of IDs')]
       array $ids,
   ) {
       foreach ($ids as $id) {
           echo "Processing ID: $id\n";
       }
   };
   ```

- Sub commands

   Consider `Source/Commands/Users/CreateCommand.php`
   
   ```php
   use PhpRepos\Console\Attributes\Argument;
   use PhpRepos\Console\Attributes\Description;
   
   /**
    * Creates a new user in the system.
    */
   return function (
       #[Argument, Description('The username for the new user')]
       string $username,
   ) {
       echo "Creating user: $username\n";
   };
   ```
   
   Run it:
   
   ```shell
   ./console users create johndoe
   ```
   
   Help output:
   
   ```shell
   ./console users create --help
   ```

## Advanced Customization

To customize command loading, create a file like `cli` in your project root. Here’s an example:

```php
#!/usr/bin/env php
<?php

use PhpRepos\Console\Input;
use PhpRepos\FileManager\Path;
use function PhpRepos\Console\Runner\from_path;
use function PhpRepos\Console\Runner\run;
use function PhpRepos\FileManager\Paths\filename;
use function PhpRepos\FileManager\Paths\root;

$entrypoint = filename($_SERVER['SCRIPT_FILENAME']);
$help_text = <<<EOD
Usage: $entrypoint [-h | --help][-v]
               <command> [<options>] [<args>]
This is a custom help output for the console
EOD;

global $argv;

$help_options = getopt('h', ['help'], $command_index);
// Only care about the first help
$wants_help = (isset($help_options['h']) || isset($help_options['help'])) && $command_index === 2;

$offset = $wants_help ? 2  : 1;

$inputs = Input::make(array_slice($argv, $offset));
$commands_directory = Path::from(root())->sub('Source/Commands');
$command_handlers = from_path($commands_directory);

$command_handlers->add('welcome', fn () => echo 'hello world!');

exit(run($command_handlers, $inputs, $entrypoint, $help_text, $wants_help, $commands_directory));
```

This changes the command directory to `Src/MyCommands` and uses `.php` as the suffix. Add `cli` to your `phpkg.config.json` under `entry-points` for it to work.
