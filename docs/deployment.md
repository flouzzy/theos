# Deployment Guide

Production deployment guide for Le Rocher Académie.

## Production Requirements

- Docker & Docker Compose
- Domain name with SSL certificate
- PostgreSQL database (managed or self-hosted)
- Email service (Brevo account)
- At least 2GB RAM, 20GB storage

## Environment Configuration

### Production .env

```bash
APP_ENV=prod
APP_SECRET=<generate-secure-secret>
DATABASE_URL=postgresql://user:pass@host:5432/dbname
MAILER_DSN=brevo+api://YOUR_API_KEY@default
BREVO_API_KEY=your_api_key
BREVO_LIST_ID=your_list_id
BREVO_FROM_EMAIL=no-reply@yourdomain.com
BREVO_FROM_NAME="Le Rocher Académie"
```

## Deployment Steps

### 1. Server Setup

```bash
# Clone repository
git clone https://github.com/egliselerocher/academie.git
cd academie

# Checkout production branch
git checkout main
```

### 2. Build Production Images

```bash
docker compose -f compose.yaml -f compose.prod.yaml build --no-cache
```

### 3. Start Services

```bash
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

### 4. Run Migrations

```bash
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

### 5. Clear & Warm Cache

```bash
docker compose exec php bin/console cache:clear --env=prod
docker compose exec php bin/console cache:warmup --env=prod
```

### 6. Build Assets

```bash
docker compose exec php bin/console tailwind:build --minify
```

## Database Backup

### Automated Backups

Configure daily backups:
```bash
# Add to crontab
0 2 * * * docker compose exec database pg_dump -U app app > /backups/db-$(date +\%Y\%m\%d).sql
```

### Manual Backup

```bash
docker compose exec database pg_dump -U app app > backup_$(date +%Y%m%d).sql
```

## Monitoring

- Monitor server resources (CPU, RAM, Disk)
- Track application logs
- Set up error reporting (Sentry recommended)
- Monitor database performance

## Updates

### Deploying Updates

```bash
# Pull latest code
git pull origin main

# Rebuild images if needed
docker compose -f compose.yaml -f compose.prod.yaml build

# Run migrations
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

# Clear cache
docker compose exec php bin/console cache:clear --env=prod

# Restart services
docker compose -f compose.yaml -f compose.prod.yaml restart
```

## Rollback

```bash
# Revert to previous version
git checkout <previous-commit>

# Rebuild and restart
docker compose -f compose.yaml -f compose.prod.yaml up -d --build
```

## Security Checklist

- [ ] HTTPS enabled
- [ ] Strong APP_SECRET generated
- [ ] Database credentials secure
- [ ] Firewall configured
- [ ] Regular backups enabled
- [ ] Error reporting configured
- [ ] Security updates applied

## Performance Optimization

- Enable PHP OPcache
- Use Redis for cache
- Configure CDN for assets
- Optimize database indexes
- Enable HTTP/2

See [Architecture](architecture.md) for more performance details.
