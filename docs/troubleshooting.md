# Troubleshooting

Common issues and solutions for Le Rocher Académie.

## Installation Issues

### Port Already in Use

**Error**: `Bind for 0.0.0.0:8096 failed: port is already allocated`

**Solution**: Change ports in `.env`:
```bash
HTTP_PORT=8085
HTTPS_PORT=8086
docker compose down
docker compose up -d
```

### Database Connection Failed

**Error**: `Connection refused` or `could not connect to server`

**Solution**:
```bash
# Check database container
docker compose ps
docker compose logs database

# Restart database
docker compose restart database

# Wait for database to be ready
docker compose exec php bin/console wait-for-it database:5432
```

### Permission Denied

**Error**: Permission errors in `var/` directory

**Solution**:
```bash
docker compose exec php chown -R www-data:www-data var/
docker compose exec php chmod -R 775 var/
```

## Development Issues

### Cache Not Clearing

**Solution**:
```bash
# Hard clear
docker compose exec php rm -rf var/cache/*
docker compose exec php bin/console cache:clear
```

### Assets Not Loading

**Solution**:
```bash
# Rebuild assets
docker compose exec php bin/console tailwind:build

# Check asset paths
docker compose exec php bin/console debug:router
```

### Migrations Fail

**Error**: Migration already executed or schema mismatch

**Solution**:
```bash
# Check migration status
docker compose exec php bin/console doctrine:migrations:status

# Force version (use with caution)
docker compose exec php bin/console doctrine:migrations:version <version> --add

# Reset database (dev only)
docker compose exec php bin/console doctrine:database:drop --force
docker compose exec php bin/console doctrine:database:create
docker compose exec php bin/console doctrine:migrations:migrate
```

## Testing Issues

### Test Database Not Found

**Solution**:
```bash
docker compose exec database createdb -U app app_test
docker compose exec php bin/console doctrine:migrations:migrate --env=test
docker compose exec php bin/console doctrine:fixtures:load --env=test
```

### Tests Hanging

**Solution**: Check for infinite loops or missing mocks for external services.

## Production Issues

### 500 Internal Server Error

**Check**:
1. Application logs: `docker compose logs php`
2. Error logs: `var/log/prod.log`
3. Check `.env` configuration
4. Ensure cache is cleared

### Slow Performance

**Solutions**:
- Enable OPcache
- Use Redis for caching
- Optimize database queries
- Check server resources

### Email Not Sending

**Check**:
1. MAILER_DSN configuration
2. Brevo API key validity
3. Email logs: `docker compose logs php`
4. Test with: `docker compose exec php bin/console mailer:test`

## Getting Help

1. Check [Documentation](../README.md)
2. Search [existing issues](https://github.com/egliselerocher/academie/issues)
3. Ask in discussions
4. Contact support

Still having issues? Create a detailed bug report.
