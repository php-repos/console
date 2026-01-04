# Upgrade Guide

## Upgrading from v6.0.0 to v7.0.0

Version 7.0.0 introduces Natural Architecture with clear separation of concerns. This guide covers the breaking changes you need to address when upgrading.

### 1. Update Attribute Namespaces in Command Files

All attribute classes have moved to the `Business\Attributes` namespace.

**Before (v6.0.0):**
```php
<?php

use PhpRepos\Console\Attributes\Argument;
use PhpRepos\Console\Attributes\Description;
use PhpRepos\Console\Attributes\ExcessiveArguments;
use PhpRepos\Console\Attributes\LongOption;
use PhpRepos\Console\Attributes\ShortOption;

return function (
    #[LongOption('email'), Description('User email')]
    string $email,
) {
    // Your code
};
```

**After (v7.0.0):**
```php
<?php

use PhpRepos\Console\Business\Attributes\Argument;
use PhpRepos\Console\Business\Attributes\Description;
use PhpRepos\Console\Business\Attributes\ExcessiveArguments;
use PhpRepos\Console\Business\Attributes\LongOption;
use PhpRepos\Console\Business\Attributes\ShortOption;

return function (
    #[LongOption('email'), Description('User email')]
    string $email,
) {
    // Your code
};
```

**Action Required:** Update the namespace imports in all your command files.

### 2. Update Custom Console Scripts

If you have a custom console entry point, you need to update it to use the new API.

**Before (v6.0.0):**
```php
#!/usr/bin/env php
<?php

use function PhpRepos\Console\Runner\from_path;
use function PhpRepos\Console\Runner\run;

global $argv;

$entrypoint = $_SERVER['SCRIPT_FILENAME'];
$inputs = array_slice($argv, 1);
$handlers = from_path('Commands');

exit(run($handlers, $inputs, $entrypoint));
```

**After (v7.0.0):**
```php
#!/usr/bin/env php
<?php

use PhpRepos\Console\Business\Finder;
use PhpRepos\Console\Business\Command;
use PhpRepos\Console\UI;
use PhpRepos\Console\Infra\CLI;

global $argv;

$entrypoint = 'console';
$short_options = 'h';
$long_options = ['help'];
$options = getopt($short_options, $long_options, $offset);
$wants_help = isset($options['h']) || isset($options['help']);
$inputs = array_slice($argv, $offset);

$outcome = Finder\path('Commands', 'Command.php');

if (!$outcome->success) CLI\error($outcome->message) && exit(1);

$command_handlers = $outcome->data['handlers'];

$outcome = Command\find($command_handlers, $inputs);

if (!$outcome->success) {
    if (!$wants_help) CLI\error($outcome->message);

    $outcome = Command\all($command_handlers);
    CLI\write(UI\short_help($outcome->data['commands'], $entrypoint)) && exit($wants_help ? 0 : 1);
}

$command = $outcome->data['name'];
$handler = $outcome->data['handler'];

$outcome = Command\describe($handler);
if (!$outcome->success) CLI\error($outcome->message) && exit(1);

$description = $outcome->data;

if ($wants_help)
    CLI\write(UI\long_help($description, $entrypoint, $command)) && exit(0);

$outcome = Command\run($command, $handler, $command_handlers, $inputs);

if (!$outcome->success)
    CLI\error($outcome->message)
        && CLI\write(UI\long_help($description, $entrypoint, $command))
        && exit($outcome->data['exit_code'] ?? 1);

exit($outcome->data['exit_code'] ?? 0);
```

**Key Changes:**
- Use `Finder\path()` instead of `from_path()` to discover commands
- Use `getopt()` to handle help flags (`-h`, `--help`)
- All Business layer functions now return `Outcome` objects with `success`, `message`, and `data` properties
- Use `Command\find()`, `Command\all()`, `Command\describe()`, and `Command\run()` for command operations
- Use `UI\short_help()` and `UI\long_help()` for formatting help output
- Use `CLI\error()` and `CLI\write()` for console output

**Action Required:** If you have a custom console script, update it to match the new structure above.

### 3. Update Signal/Event Observers

Signal classes have moved to the `Business\Signals` namespace, and some signal names have changed.

**Before (v6.0.0):**
```php
use PhpRepos\Console\Signals\ConsoleSessionStarted;
use PhpRepos\Console\Signals\RunningConsoleCommand;
use PhpRepos\Console\Signals\CommandExecutionCompleted;
use function PhpRepos\Observer\API\Bus\observe;

observe(ConsoleSessionStarted::class, function ($signal) {
    // Handle event
});
```

**After (v7.0.0):**
```php
use PhpRepos\Console\Business\Signals\RunningConsoleCommand;
use PhpRepos\Console\Business\Signals\CommandExecutionCompleted;
use PhpRepos\Console\Business\Signals\CommandExecutionFailed;
use function PhpRepos\Observer\API\Bus\observe;

observe(RunningConsoleCommand::class, function ($signal) {
    // Handle event
});
```

**Signal Changes:**
- `ConsoleSessionStarted` - **Removed** (no longer used)
- `RunningConsoleCommand` - Moved to `Business\Signals\RunningConsoleCommand`
- `CommandExecutionCompleted` - Moved to `Business\Signals\CommandExecutionCompleted`
- `CommandExecutionFailed` - **New signal** for handling execution failures

**Action Required:** Update namespace imports for any signal observers you have configured.

## Rebuilding Your Project

After making these changes, rebuild your project:

```bash
phpkg build
```

## What Stayed the Same

- Command file structure (still return a callable with attributes)
- Command discovery from `Commands/` directory
- Parameter types (string, int, bool, array)
- Subcommand directory structure
- Help flags (`-h` and `--help`)
