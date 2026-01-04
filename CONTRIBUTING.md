# Contributing to Console Package

Thank you for considering contributing to the Console Package! This document provides guidelines and principles to help you contribute effectively.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Architecture Principles](#architecture-principles)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Pull Request Process](#pull-request-process)

## Code of Conduct

- Be respectful and constructive in all interactions
- Focus on what is best for the community
- Show empathy towards other community members
- Accept constructive criticism gracefully

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- `phpkg` package manager
- Git

### Setting Up Development Environment

1. Fork and clone the repository:
```bash
git clone https://github.com/YOUR_USERNAME/console.git
cd console
```

2. Install dependencies:
```bash
phpkg build
```

3. Run tests:
```bash
cd ~/phpkg/console && phpkg build && cd build && phpkg run test-runner run
```

## Architecture Principles

The Console Package follows **Natural Architecture**, which provides clear separation of concerns across three layers. Understanding these principles is crucial for contributing.

### The Three Layers

#### 1. Business Layer (`Source/Business/`)

**Purpose:** Define *what* the system does, not *how*.

**Principles:**
- Contains only specifications and contracts
- No implementation details (no reflection, no I/O, no parsing)
- Returns `Outcome` objects with predictable structure
- Depends only on Solution and Infrastructure layers
- Functions should be pure specifications

**Example:**
```php
// GOOD: Business function delegates to Solution
function describe(callable $command): Outcome
{
    $description = Handlers\get_description($command);
    $arguments = Handlers\get_arguments($command);

    return new Outcome(true, '', [
        'description' => $description,
        'arguments' => $arguments,
    ]);
}

// BAD: Business function contains implementation
function describe(callable $command): Outcome
{
    $reflection = new ReflectionFunction($command);
    $docblock = $reflection->getDocComment();
    // ... parsing logic
}
```

**Key Components:**
- `Command.php` - Command operations (find, all, describe, run)
- `Finder.php` - Command discovery
- `Outcome.php` - Return value wrapper
- `Attributes/` - Parameter definition attributes
- `Signals/` - Event objects for observer pattern

#### 2. Solution Layer (`Source/Solution/`)

**Purpose:** Implement *how* things work.

**Principles:**
- Contains actual implementation logic
- Uses reflection, parsing, data transformation
- Called by Business layer, never calls Business layer
- Can depend on Infrastructure layer
- Pure functions without side effects where possible

**Example:**
```php
// GOOD: Solution function contains implementation
function get_description(callable $command): string
{
    $docblock = Reflections\docblock_to_text($command);
    return $docblock ?: 'No description provided for the command.';
}

// GOOD: Solution function uses complex logic
function match_best_command_from_inputs(string $input_command, array $handlers): array
{
    $best_score = [-1, -1];
    // ... matching algorithm
    return $best_match;
}
```

**Key Components:**
- `Handlers.php` - Command handler operations
- `Inputs.php` - Input processing
- `Execution.php` - Command execution logic
- `Paths.php` - Filesystem path operations
- `Data/` - Data structures
- `Exceptions/` - Domain exceptions

#### 3. Infrastructure Layer (`Source/Infra/` and `Source/UI.php`)

**Purpose:** Handle external concerns and formatting.

**Principles:**
- I/O operations (CLI, filesystem)
- Data formatting (UI rendering)
- External library interactions
- System-level operations

**Example:**
```php
// GOOD: CLI function handles output
function error(string $string): bool
{
    return write("\e[91m$string\e[39m" . PHP_EOL);
}
```

**Key Components:**
- `CLI.php` - Console I/O
- `Arrays.php` - Array utilities
- `Strings.php` - String utilities
- `Filesystem.php` - File operations
- `Reflections.php` - Reflection utilities

### Layer Dependencies

```
Business → Solution → Infrastructure
   ↓          ↓
   └──────────┴────────→ Infrastructure
```

**Rules:**
- Business can call Solution and Infrastructure
- Solution can call Infrastructure
- Infrastructure has no dependencies on other layers
- **Never reverse these dependencies**

### The Outcome Pattern

All Business layer functions return `Outcome` objects for predictable, consistent results:

```php
class Outcome
{
    public bool $success;
    public string $message;
    public array $data;
}
```

**Usage:**
```php
$outcome = Command\find($handlers, $inputs);

if ($outcome->success) {
    // Use $outcome->data
} else {
    // Handle $outcome->message
}
```

**Guidelines:**
- Always return `Outcome` from Business functions
- Set `success` to `true` for successful operations
- Use `message` for error descriptions
- Put result data in `data` array with meaningful keys
- Keep `data` structure consistent for the same function

## Development Workflow

### Branch Naming

- Feature: `feature/description`
- Bug fix: `fix/description`
- Architecture: `arch/description`
- Documentation: `docs/description`

### Commit Messages

Follow conventional commit format:

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `refactor`: Code refactoring
- `docs`: Documentation changes
- `test`: Test additions/changes
- `arch`: Architecture changes

**Examples:**
```
feat(business): add Command\validate function

Add validation for command handlers before execution.
Returns Outcome with validation errors in data array.

Closes #123
```

```
refactor(solution): extract input parsing to Inputs namespace

Move input extraction logic from Handlers to new Inputs
namespace for better separation of concerns.
```

## Coding Standards

### General Principles

1. **Separation of Concerns**: Each function should do one thing well
2. **No Premature Abstraction**: Keep it simple until complexity is needed
3. **Explicit Over Implicit**: Make intentions clear
4. **Functional Style**: Prefer pure functions without side effects

### PHP Standards

- Use PHP 8.2+ features (attributes, named arguments, etc.)
- Follow PSR-12 coding standard
- Use type hints for all parameters and return types
- Use readonly properties where applicable

### Naming Conventions

**Functions:**
- Use verb phrases: `find()`, `get_description()`, `extract_inputs()`
- Keep names descriptive but concise
- Use snake_case for function names

**Variables:**
- Use descriptive names: `$command_handlers`, `$input_command`
- Avoid abbreviations unless widely understood
- Use snake_case for variables

**Classes/Attributes:**
- Use PascalCase: `Outcome`, `LongOption`, `CommandParameter`
- Name should describe what it is, not what it does

### Documentation

All functions must have PHPDoc comments:

```php
/**
 * Find a command handler matching the given inputs.
 *
 * Searches through available command handlers to find the best match for
 * the provided inputs. Returns the matched command name and its handler,
 * or an error if no match is found.
 *
 * @param array $command_handlers Associative array of command names to handlers
 * @param array $inputs Array of command-line input arguments
 * @return Outcome Success with ['name' => string, 'handler' => callable]
 */
function find(array $command_handlers, array $inputs): Outcome
{
    // Implementation
}
```

**Requirements:**
- Summary line (what the function does)
- Detailed description (how it works, edge cases)
- `@param` for each parameter with type and description
- `@return` with type and description
- `@throws` for any exceptions
- Examples for complex functions

### File Organization

**Business Layer:**
```
Source/Business/
├── Command.php          # All Command\* functions
├── Finder.php           # All Finder\* functions
├── Outcome.php          # Outcome class
├── Attributes/          # Attribute classes
└── Signals/             # Signal/event classes
```

**Solution Layer:**
```
Source/Solution/
├── Handlers.php         # Command handler operations
├── Inputs.php           # Input processing
├── Execution.php        # Execution logic
├── Paths.php            # Path operations
├── Data/                # Data structures
└── Exceptions/          # Domain exceptions
```

**Infrastructure Layer:**
```
Source/Infra/
├── CLI.php              # Console I/O
├── Arrays.php           # Array utilities
├── Strings.php          # String utilities
├── Filesystem.php       # File operations
└── Reflections.php      # Reflection utilities

Source/
└── UI.php               # UI formatting functions
```

## Testing Guidelines

### Test Structure

Tests are located in `Tests/` directory:

- `ConsoleTest.php` - Integration tests for console script
- `ConsoleHelpTest.php` - Help text formatting tests
- `HelperCommands/` - Sample commands for testing

### Writing Tests

```php
use function PhpRepos\TestRunner\Runner\test;

test(
    title: 'it should describe what is being tested',
    case: function () {
        // Arrange
        $handlers = ['test' => fn() => 'output'];
        $inputs = ['test'];

        // Act
        $outcome = Command\find($handlers, $inputs);

        // Assert
        assert_true($outcome->success);
        assert_equal('test', $outcome->data['name']);
    },
    before: function () {
        // Setup
    },
    after: function () {
        // Cleanup
    }
);
```

### Test Coverage

- Write tests for new Business layer functions
- Test both success and failure cases
- Test edge cases and boundary conditions
- Ensure Solution layer functions work correctly
- Integration tests for full command execution

### Running Tests

```bash
cd ~/phpkg/console
phpkg build
cd build
phpkg run test-runner run
```

Run specific test file:
```bash
phpkg run test-runner run --filter=ConsoleTest
```

## Pull Request Process

### Before Submitting

1. **Run all tests** and ensure they pass
2. **Run build** and verify no errors
3. **Update documentation** if adding features
4. **Add tests** for new functionality
5. **Follow architecture principles** strictly
6. **Add docblocks** to all new functions

### PR Requirements

1. **Clear title** describing the change
2. **Description** explaining:
   - What changed
   - Why it changed
   - How it was tested
3. **Reference issues** if applicable
4. **Small, focused changes** - one feature per PR
5. **No breaking changes** unless discussed

### PR Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Refactoring
- [ ] Documentation
- [ ] Architecture change

## Changes Made
- Change 1
- Change 2

## Testing
- [ ] All tests pass
- [ ] Added new tests
- [ ] Manual testing completed

## Architecture Compliance
- [ ] Follows Natural Architecture layers
- [ ] Business layer only contains specifications
- [ ] Solution layer contains implementation
- [ ] No circular dependencies

## Documentation
- [ ] Updated README if needed
- [ ] Added/updated docblocks
- [ ] Updated UPGRADE.md if breaking change

## Related Issues
Closes #123
```

### Review Process

1. Maintainers review code for:
   - Architecture compliance
   - Code quality
   - Test coverage
   - Documentation
2. Address review feedback
3. Once approved, PR will be merged

### After Merge

- Delete your feature branch
- Monitor for any issues
- Respond to follow-up discussions

## Questions?

- Open an issue for questions
- Tag it with `question` label
- Be specific about what you need help with

## License

By contributing, you agree that your contributions will be licensed under the same license as the project (MIT License).
