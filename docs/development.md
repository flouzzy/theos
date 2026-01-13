# Development Guide

This guide covers the local development workflow for Le Rocher Académie.

## Development Environment

The project uses Docker for a consistent development environment across all platforms.

### Starting Development

```bash
# Start all services
docker compose up -d

# Watch Tailwind CSS changes
docker compose exec php bin/console tailwind:build --watch

# Watch logs
docker compose logs -f php
```

### Common Development Commands

```bash
# Clear cache
docker compose exec php bin/console cache:clear

# Create a new migration
docker compose exec php bin/console make:migration

# Run migrations
docker compose exec php bin/console doctrine:migrations:migrate

# Load fixtures
docker compose exec php bin/console doctrine:fixtures:load

# Create a new controller
docker compose exec php bin/console make:controller

# Create a new entity
docker compose exec php bin/console make:entity
```

## Code Quality

### Running Tests

```bash
# Run all tests
docker compose exec php bin/phpunit

# Run specific test file
docker compose exec php bin/phpunit tests/SmokeTest.php

# Run with coverage
docker compose exec php bin/phpunit --coverage-html var/coverage
```

### Code Standards

We follow PSR-12 coding standards. Use PHP-CS-Fixer:

```bash
# Check code style
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix code style
docker compose exec php vendor/bin/php-cs-fixer fix
```

## Database Management

### Creating Migrations

```bash
# Generate migration from entity changes
docker compose exec php bin/console make:migration

# Execute migrations
docker compose exec php bin/console doctrine:migrations:migrate
```

### Fixtures

Fixtures provide sample data for development:

```bash
# Load all fixtures (WARNING: purges database)
docker compose exec php bin/console doctrine:fixtures:load

# Append fixtures without purging
docker compose exec php bin/console doctrine:fixtures:load --append
```

## Debugging

### XDebug

XDebug is pre-configured. Enable it:

```bash
XDEBUG_MODE=debug docker compose up -d
```

Configure your IDE to listen on port 9003.

### Symfony Profiler

Access the profiler toolbar at the bottom of each page in dev mode.

View detailed profiler data:
```
http://localhost:8095/_profiler
```

### Database Queries

```bash
# View SQL queries
docker compose exec database psql -U app -d app

# Run custom SQL
docker compose exec php bin/console dbal:run-sql "SELECT * FROM user LIMIT 10"
```

## Making Changes

### Adding a New Feature

1. Create feature branch
2. Write tests first (TDD)
3. Implement feature
4. Update documentation
5. Run tests and fix issues
6. Submit pull request

### Updating Dependencies

```bash
# Update Composer dependencies
docker compose exec php composer update

# Update npm packages (if needed)
docker compose exec php npm update
```

## Performance Optimization

### Cache Warming

```bash
docker compose exec php bin/console cache:warmup
```

### Database Optimization

```bash
# Analyze database performance
docker compose exec database psql -U app -d app -c "VACUUM ANALYZE"
```

## Troubleshooting

See [Troubleshooting Guide](troubleshooting.md) for common development issues.

## Next Steps

- Review [Architecture Overview](architecture.md)
- Learn about [Testing Strategy](testing.md)
- Explore [Features](features.md)
