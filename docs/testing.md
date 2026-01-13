# Testing Guide

Le Rocher Académie uses PHPUnit for comprehensive testing coverage.

## Test Structure

```
tests/
├── Smoke Test.php              # Smoke tests for all public routes
├── RegistrationFunctionalTest.php  # Registration flow tests
└── (additional functional tests)
```

## Running Tests

### Run All Tests
```bash
docker compose exec php bin/phpunit
```

### Run Specific Test File
```bash
docker compose exec php bin/phpunit tests/SmokeTest.php
```

### Run with Coverage
```bash
docker compose exec php bin/phpunit --coverage-html var/coverage
```

## Test Types

### 1. Smoke Tests

Verify all pages load without errors:

```php
// tests/SmokeTest.php
#[DataProvider('providePublicUrls')]
public function testPageIsSuccessful(string $url): void
{
    $client = static::createClient();
    $client->request('GET', $url);
    $this->assertResponseIsSuccessful();
}
```

### 2. Functional Tests

Test complete user flows:

```php
// tests/RegistrationFunctionalTest.php
public function testRegistration(): void
{
    $client = static::createClient();
    $crawler = $client->request('GET', '/register');
    
    $form = $crawler->filter('button[type="submit"]')->form();
    // Fill form and submit
    // Assert user created, email sent, etc.
}
```

### 3. Unit Tests

Test individual components in isolation (add as needed).

## Testing Environment

### Test Database

Tests use a separate `app_test` database configured in `.env.test`:

```
DATABASE_URL="postgresql://app:!ChangeMe!@database:5432/app_test"
```

### Setup Test Database

```bash
# Create test database
docker compose exec database createdb -U app app_test

# Run migrations
docker compose exec php bin/console doctrine:migrations:migrate --env=test

# Load fixtures
docker compose exec php bin/console doctrine:fixtures:load --env=test
```

## Writing Tests

### Test Naming Convention

- `test*` prefix for test methods
- Descriptive names: `testUserCanRegisterWithValidData()`

### Assertions

Common assertions:
```php
$this->assertResponseIsSuccessful();
$this->assertSelectorTextContains('h1', 'Welcome');
$this->assertResponseRedirects('/dashboard');
$this->assertEmailCount(1);
```

### Data Providers

Use data providers for multiple test cases:

```php
public static function provideInvalidEmails(): \Generator
{
    yield ['invalid'];
    yield ['@example.com'];
    yield ['user@'];
}

#[DataProvider('provideInvalidEmails')]
public function testInvalidEmailRejected(string $email): void
{
    // Test logic
}
```

## Best Practices

1. **Isolate Tests**: Each test should be independent
2. **Clean Database**: Use transactions or reload fixtures
3. **Test Edge Cases**: Not just happy paths
4. **Mock External Services**: Don't call real APIs in tests
5. **Fast Tests**: Keep tests quick to encourage frequent running

## Continuous Integration

Tests run automatically on:
- Pull requests
- Commits to main branch
- Before deployment

## Coverage Goals

- **Minimum**: 70% code coverage
- **Target**: 80%+ coverage
- **Critical paths**: 100% coverage (auth, payment)

## Debugging Tests

### Run with Verbose Output
```bash
docker compose exec php bin/phpunit --verbose
```

### Debug Specific Test
```bash
docker compose exec php bin/phpunit --filter testRegistration
```

### Enable XDebug for Tests
```bash
XDEBUG_MODE=coverage docker compose exec php bin/phpunit
```

## Next Steps

- Review [Development Guide](development.md)
- Understand [Architecture](architecture.md)
- See [Contributing Guide](contributing.md)
