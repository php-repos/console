# Console Package for PHP Developers

The Console package is a powerful tool for PHP developers to create CLI commands and applications easily.
This document will guide you through the process of using the Console package with the `phpkg` package manager.

## Installation

To use the Console package, you need to install it using the `phpkg` package manager. Run the following command:

```shell
phpkg add https://github.com/php-repos/console.git
```

## Access and running console

Users can access the Console package's CLI commands using either of the following methods:

1. **Using `php console`**

   To run Console package commands, after building using `phpkg`, users can use the `php console` command followed by the desired command and its options.

   Example:
   ```bash
   php console make-user --email=johndoe@example.com johndoe -p=secret
   ```

2. **Using `./console`**

   After building the Console package using `phpkg`, users can also run commands by using `./console` followed by 
the desired command and its options.

   Example:
   ```bash
   ./console make-user --email=johndoe@example.com johndoe -p=secret
   ```
Both methods provide access to the same set of CLI commands and options provided by the Console package. Users can
choose the one that best fits their workflow and preferences.

## Creating Commands

After installing the Console package, you can create new commands under the `Source/Commands` directory. The command
filename must be in PascalCase, corresponding to the desired command name. For example, if you want to create a
`make-user` command, create a file named `MakeUserCommand.php` in the Source/Commands directory.

### Defining Command Options

The Console package allows you to define command options using attributes. Here's how you can define options in
your command file:

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
// Your command logic here
};
```

You can use both long and short options, and Console supports built-in PHP types as well as optional types for 
the command options. For example, you can define flag options with a default value.

### Defining Command Arguments

To receive command arguments, you need to define the `Argument` attribute for the parameters in your command function.
Here's an example:

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
    // Your command logic here
};
```

### Adding Command Help

You can create helpful documentation for your commands by adding comments to your command files. Here's an example:

```php
use PhpRepos\Console\Attributes\Argument;
use PhpRepos\Console\Attributes\LongOption;
use PhpRepos\Console\Attributes\ShortOption;

/**
* This command creates a user by the given email and the given password
* When you need to force the command to create a user, you can pass the force option
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
  // Your command logic here
};
```

Users can view the command help by running php `./console -h` or `./console --help`, which will display a list 
of available commands and their descriptions.

### Documenting Parameters

You can also add documentation for each parameter in your command function. Here's an example:

```php
use PhpRepos\Console\Attributes\Argument;
use PhpRepos\Console\Attributes\Description;
use PhpRepos\Console\Attributes\LongOption;
use PhpRepos\Console\Attributes\ShortOption;

/**
 * This command creates a user by the given email and the given password
 * When you need to force the command to create a user, you can pass the force option
 */
return function (
    #[LongOption('email'), Description('The user email for the new user')]
    string $email,
    #[Argument, Description('Pass a username for the user')]
    string $username,
    #[ShortOption('p'), Description('User password that user wants to sign in')]
    string $password,
    #[LongOption('force'), ShortOption('f'), Description('You can force the creation of user')]
    ?bool  $force = false,
) {
    // Your command logic here
};
```

Users can view detailed help for a specific command by running `./console -h <command>` or
`./console --help <command>`, which will display information about the command's arguments and options,
along with their descriptions. For example, for the previous command, the help looks like:

```shell
Usage: console make-user [<options>] <username>

Description:
 This command creates a user by the given email and the given password
 When you need to force the command to create a user, you can pass the force option

Arguments:
  <username> Pass a username for the user

Options:
  --email <email> The user email for the new user
  -p <password>   User password that user wants to sing in
  -f, --force     You can force the creation of user

```

## Documentation

All documents can be found under [documentation](https://phpkg.com/packages/console/documentations/getting-started).