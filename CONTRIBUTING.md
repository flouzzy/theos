# Contributing to Theos (Le Rocher Académie)

First off, thank you for considering contributing to Theos! It's people like you that make this open-source project such a great community.

Following these guidelines helps to communicate that you respect the time of the developers managing and developing this open source project. In return, they should reciprocate that respect in addressing your issue, assessing changes, and helping you finalize your pull requests.

## Code of Conduct

By participating in this project, you are expected to uphold our Code of Conduct. Please be respectful, inclusive, and professional in all interactions. Harassment and unacceptable behavior will not be tolerated.

## How Can I Contribute?

### Reporting Bugs

This section guides you through submitting a bug report. Following these guidelines helps maintainers and the community understand your report, reproduce the behavior, and find related reports.

Before creating bug reports, please check existing issues as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

*   **Use a clear and descriptive title** for the issue to identify the problem.
*   **Describe the exact steps which reproduce the problem** in as many details as possible.
*   **Provide specific examples to demonstrate the steps**. Include links to files or copy/pasteable snippets.
*   **Describe the behavior you observed after following the steps** and point out what exactly is the problem with that behavior.
*   **Explain which behavior you expected to see instead and why.**
*   **Include screenshots and animated GIFs** which show you following the described steps and clearly demonstrate the problem.
*   **Include your environment details** (PHP version, Symfony version, Docker setup, Browser, OS, etc.).

### Suggesting Enhancements

This section guides you through submitting an enhancement suggestion, including completely new features and minor improvements to existing functionality.

*   **Use a clear and descriptive title** for the issue to identify the suggestion.
*   **Provide a step-by-step description of the suggested enhancement** in as many details as possible.
*   **Provide specific examples to demonstrate the steps**.
*   **Describe the current behavior** and **explain which behavior you expected to see instead** and why.
*   **Explain why this enhancement would be useful** to most users.

### Pull Requests

The process described here has several goals:
*   Maintain Theos's quality
*   Fix problems that are important to users
*   Engage the community in working toward the best possible platform
*   Enable a sustainable system for maintainers to review contributions

Please follow these steps to have your contribution considered by the maintainers:

1.  **Fork the repository and create your branch from `main`.**
    `git checkout -b feature/amazing-feature` or `git checkout -b fix/issue-number`
2.  **If you've added code that should be tested, add tests.** (See the Testing section below).
3.  **If you've changed APIs, update the documentation.**
4.  **Ensure the test suite passes.** (`docker compose exec php bin/phpunit`)
5.  **Make sure your code lints.** Follow the Code Standards section.
6.  **Issue that pull request!**

## Styleguides

### Git Commit Messages

*   Use the present tense ("Add feature" not "Added feature")
*   Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
*   Limit the first line to 72 characters or less
*   Reference issues and pull requests liberally after the first line
*   When only changing documentation, include `[ci skip]` in the commit title
*   Follow standard conventional commits format:
    *   `feat:` A new feature
    *   `fix:` A bug fix
    *   `docs:` Documentation only changes
    *   `style:` Changes that do not affect the meaning of the code (white-space, formatting, missing semi-colons, etc)
    *   `refactor:` A code change that neither fixes a bug nor adds a feature
    *   `perf:` A code change that improves performance
    *   `test:` Adding missing tests or correcting existing tests
    *   `chore:` Changes to the build process or auxiliary tools and libraries such as documentation generation

### PHP and Symfony Code Standards

*   Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards.
*   Use strictly typed PHP (`declare(strict_types=1);` where appropriate).
*   Use type hints for all properties, arguments, and return types.
*   Write clear and concise PHPDoc comments, especially for complex logic or when types aren't fully expressive in PHP (e.g., array shapes).
*   Keep methods focused, small, and testable.
*   Use meaningful, descriptive variable and method names.
*   Follow standard Symfony conventions for directory structure, service configuration, and Dependency Injection.

### UI and Frontend Standards

*   Use Tailwind CSS utility classes for styling. Avoid writing custom CSS unless absolutely necessary.
*   Use Shadcn UI components for consistent design language.
*   Ensure components are responsive (mobile, tablet, desktop).
*   Keep frontend interactive logic (Turbo/Stimulus) scoped and modular.

## Local Development

To set up the project locally, please follow the comprehensive instructions in the [Installation Guide](docs/installation.md) and [Development Guide](docs/development.md).

Quick setup:
```bash
git clone git@github.com:<your-username>/theos.git
cd theos
docker compose up -d
docker compose exec php composer install
docker compose exec php bin/console doctrine:migrations:migrate
docker compose exec php bin/console doctrine:fixtures:load
```

## Testing

All code changes should be covered by tests. The project uses PHPUnit and Symfony Browser Kit.

*   **Unit Tests:** For testing isolated classes, services, and logic.
*   **Functional/Integration Tests:** For testing API endpoints, database interactions, and full request cycles.

To run the test suite:

```bash
# Run all tests
docker compose exec php bin/phpunit

# Run a specific test suite
docker compose exec php bin/phpunit tests/SmokeTest.php
```

Ensure that you do not introduce regressions and aim to maintain or improve code coverage.

Thank you for contributing! 🎉
