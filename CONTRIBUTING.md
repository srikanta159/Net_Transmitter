# Contributing to Net_Transmitter

Thank you for your interest in contributing to **Net_Transmitter**! We welcome bug reports, feature requests, documentation improvements, and pull requests.

## Development Workflow

### Requirements
- PHP 7.4 or higher (PHP 8.0 - 8.4 recommended)
- Composer 2.x
- Extensions: `sockets`, `openssl`, `apcu` (optional, for persistent connection testing)

### Setup
1. Fork the repository on GitHub.
2. Clone your fork locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/Net_Transmitter.git
   cd Net_Transmitter
   ```
3. Install dependencies:
   ```bash
   composer install
   ```

### Running Tests
Run the unit test suite with PHPUnit:
```bash
composer test
```
Or directly:
```bash
vendor/bin/phpunit --testsuite Unit
```

### Coding Standards
- Follow **PSR-12** coding style guidelines.
- Use 4 spaces for indentation in PHP files.
- Ensure all new public or protected methods have clear docblocks with parameter and return type declarations.
- When implementing internal PHP interfaces, include `#[\ReturnTypeWillChange]` where appropriate to maintain cross-version compatibility.

## Submitting Pull Requests
1. Create a descriptive feature branch (`git checkout -b feature/my-new-feature`).
2. Commit your changes with clear, semantic commit messages.
3. Make sure all tests pass and your code adheres to coding standards.
4. Push your branch to GitHub and open a Pull Request against `master`.
5. Ensure all GitHub Actions CI checks pass.
