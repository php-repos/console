# Contributing

## Setting Up the Development Environment

To contribute to the Console Package, follow these steps:

1. Clone the repository:

   ```bash
   git clone https://github.com/php-repos/console.git
   cd console
   ```

2. Install dependencies:
   ```bash
   phpkg install
   ```
3. Build the project:

   ```bash
   phpkg build
   ```

4. Contribute your changes.

5. Test your changes:

   ```bash
   cd builds/development
   phpkg run https://github.com/php-repos/test-runner.git
   ```