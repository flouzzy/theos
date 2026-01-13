# Contributing to Le Rocher Académie

Thank you for your interest in contributing!

## Code of Conduct

Be respectful, inclusive, and professional.

## How to Contribute

### Reporting Bugs

1. Check existing issues first
2. Create detailed bug report with:
   - Steps to reproduce
   - Expected vs actual behavior
   - Environment details
   - Screenshots if applicable

### Suggesting Features

1. Search existing feature requests
2. Create issue with "Feature Request" label
3. Describe use case and benefits

### Pull Requests

#### Development Workflow

1. Fork the repository
2. Create feature branch: `git checkout -b feature/amazing-feature`
3. Make changes following code standards
4. Write tests for new functionality
5. Run tests: `docker compose exec php bin/phpunit`
6. Commit with conventional commits format
7. Push and create Pull Request

#### Code Standards

- Follow PSR-12 coding standards
- Use type hints
- Write PHPDoc comments
- Keep methods focused and small
- Use meaningful variable names

#### Commit Messages

Follow conventional commits:
```
feat: add user profile editing
fix: resolve registration email bug
docs: update installation guide
test: add course enrollment tests
refactor: simplify authentication logic
```

## Development Setup

See [Development Guide](development.md) for setup instructions.

## Testing

All PRs must include tests:
- Unit tests for new services/classes
- Functional tests for new features
- Maintain or improve code coverage

## Documentation

Update documentation for:
- New features
- API changes
- Configuration changes
- Breaking changes

## Review Process

1. Automated tests must pass
2. Code review by maintainer
3. Address feedback
4. Approval and merge

## Questions?

Open a discussion or contact maintainers.

Thank you for contributing! 🎉
