# Installation Guide

This guide will help you set up **Le Rocher Académie** on your local machine or production environment.

## Prerequisites

- **Docker** & **Docker Compose** (v2.10+)
- **Git**
- At least 4GB of RAM
- Ports 8095 (HTTP), 8096 (HTTPS), 5432 (PostgreSQL) available

## Local Development Installation

### 1. Clone the Repository

```bash
git clone https://github.com/egliselerocher/academie.git
cd academie
```

### 2. Configure Environment Variables

Copy the example environment file and customize it:

```bash
cp .env .env.local
```

Key configuration variables:
- `APP_ENV=dev`
- `DATABASE_URL` - PostgreSQL connection string
- `MAILER_DSN` - Mail server configuration (uses Mailpit in dev)
- `BREVO_API_KEY` - Brevo API key (for production email)

### 3. Build and Start Docker Containers

```bash
# Build fresh images
docker compose build --pull --no-cache

# Start containers
docker compose up -d

# Wait for services to be ready
docker compose exec php bin/console wait-for-it database:5432
```

### 4. Install PHP Dependencies

```bash
docker compose exec php composer install
```

### 5. Setup Database

```bash
# Run migrations
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

# Load sample data (optional)
docker compose exec php bin/console doctrine:fixtures:load --no-interaction
```

### 6. Build Assets

Tailwind CSS is watched automatically in dev mode. If needed manually:

```bash
docker compose exec php bin/console tailwind:build --watch
```

### 7. Access the Application

- **HTTPS**: https://localhost:8096 (recommended)
- **HTTP**: http://localhost:8095
- **Mailpit** (email testing): http://localhost:8025
- **Database**: localhost:5432

**Default admin credentials** (from fixtures):
- Email: `admin@example.com`
- Password: `password`

## Production Installation

See [Deployment Guide](deployment.md) for production-specific instructions.

## Troubleshooting

### Port Already in Use

If ports 8095/8096 are already in use, modify them in `.env`:
```
HTTP_PORT=8085
HTTPS_PORT=8086
```

### Database Connection Failed

Ensure PostgreSQL container is running:
```bash
docker compose ps
docker compose logs database
```

### Permission Issues

```bash
# Fix permissions for var/ directory
docker compose exec php chown -R www-data:www-data var/
```

For more issues, see [Troubleshooting](troubleshooting.md).

## Next Steps

- Read the [Development Guide](development.md)
- Explore [Features Documentation](features.md)
- Review [Architecture Overview](architecture.md)
