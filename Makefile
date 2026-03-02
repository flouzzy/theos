# --- Variables ---
# Default Environment (dev | prod | staging | test)
ENV ?= dev

# Base Docker Compose command
DK_COMPOSE_CMD = docker compose

# Environment Configuration
ifeq ($(ENV),prod)
    # Production
    COMPOSE_FILES = -f compose.yaml -f compose.prod.yaml
    ENV_FILES = --env-file .env.prod --env-file .env.local
    SERVICE_PHP = php
else ifeq ($(ENV),staging)
    # Staging (Similar to Prod, potentially different env vars override)
    COMPOSE_FILES = -f compose.yaml -f compose.prod.yaml
    ENV_FILES = --env-file .env.prod --env-file .env.local
    SERVICE_PHP = php
else ifeq ($(ENV),test)
    # Test
    COMPOSE_FILES = -f compose.yaml
    ENV_FILES = --env-file .env.test
    SERVICE_PHP = php
else
    # Dev (Default)
    COMPOSE_FILES = -f compose.yaml -f compose.override.yaml
    # Load .env and .env.local if they exist
    ENV_FILES =
    ifneq (,$(wildcard .env))
        ENV_FILES += --env-file .env
    endif
    ifneq (,$(wildcard .env.local))
        ENV_FILES += --env-file .env.local
    endif
    SERVICE_PHP = php
endif

# Final Command
DK_COMPOSE = $(DK_COMPOSE_CMD) $(COMPOSE_FILES) $(ENV_FILES)

# Executables
PHP_EXEC = $(DK_COMPOSE) exec $(SERVICE_PHP)
SYMFONY = $(PHP_EXEC) bin/console
COMPOSER = $(PHP_EXEC) composer
REDIS_CLI = $(DK_COMPOSE) exec redis redis-cli

.PHONY: up down build logs sh start stop restart help cc vendor db-migrate db-diff install refresh hard-reset deploy tests _wait-healthy _print-links db-backup-local db-restore-docker db-migrate-docker

# --- Help ---
help:
	@echo "Le Rocher Académie Makefile Helper"
	@echo "Current Environment: $(ENV)"
	@echo ""
	@echo "🚀 Quick Start (Dev):"
	@echo "  make dev-start   - Start dev environment"
	@echo "  make dev-reset   - Reset dev with fresh database + fixtures"
	@echo "  make fixtures    - Load fixtures into database"
	@echo ""
	@echo "Docker commands:"
	@echo "  make up          - Start containers for $(ENV)"
	@echo "  make down        - Stop and remove containers"
	@echo "  make logs        - View output"
	@echo "  make sh          - Access PHP shell"
	@echo "  make build       - Rebuild images"
	@echo ""
	@echo "Dev Tools:"
	@echo "  make cc          - Clear Symfony cache"
	@echo "  make refresh     - Restart PHP + Flush Redis + Clear Cache"
	@echo "  make db-migrate  - Execute migrations"
	@echo "  make tests       - Run PHPUnit tests (forces ENV=test)"
	@echo ""
	@echo "Production Migration (from non-dockerized):"
	@echo "  make db-backup-local   - Backup local host MySQL database to a dump file"
	@echo "  make db-restore-docker - Restore dump file to the Dockerized database"
	@echo "  make db-migrate-docker - Backup, start docker, restore data, and migrate"
	@echo ""
	@echo "Production:"
	@echo "  make deploy      - Basic deployment"

# --- Quick Dev Commands ---

dev-start:
	@echo "🚀 Starting development environment..."
	$(DK_COMPOSE) up -d --remove-orphans
	@echo "✅ Development environment ready!"
	@echo "   🌐 HTTP:  http://localhost:8095"
	@echo "   🔒 HTTPS: https://localhost:8096"

dev-reset:
	@echo "🔄 Resetting development environment..."
	@echo "⚠️  This will DELETE ALL DATA in the development database!"
	@read -p "Continue? (y/N): " confirm && [ "$$confirm" = "y" ] || exit 1
	@echo "🗑️  Dropping database schema..."
	-$(SYMFONY) doctrine:schema:drop --force --full-database
	@echo "📊 Creating fresh schema..."
	$(SYMFONY) doctrine:schema:create
	@echo "🎭 Loading fixtures..."
	$(SYMFONY) doctrine:fixtures:load --no-interaction
	@echo "✅ Development environment reset complete!"

fixtures:
	@echo "🎭 Loading fixtures..."
	$(SYMFONY) doctrine:fixtures:load --no-interaction
	@echo "✅ Fixtures loaded!"

# --- Migration from non-dockerized ---

db-backup-local:
	@echo "💾 Backing up local host database..."
	@# Adjust the database name, user, password, and host if necessary for the local setup
	mysqldump -h 127.0.0.1 -P 3306 -u root -p app > local_db_backup.sql
	@echo "✅ Local database backed up to local_db_backup.sql"

db-restore-docker:
	@echo "📥 Restoring dump to Docker database..."
	@if [ ! -f local_db_backup.sql ]; then \
		echo "❌ local_db_backup.sql not found!"; \
		exit 1; \
	fi
	@echo "Copying dump file to database container..."
	$(DK_COMPOSE_CMD) cp local_db_backup.sql database:/tmp/local_db_backup.sql
	@echo "Restoring data..."
	$(DK_COMPOSE) exec -T database mysql -u app -p"!ChangeMe!" app < local_db_backup.sql || true
	@echo "✅ Database restored!"

db-migrate-docker: db-backup-local up db-restore-docker db-migrate
	@echo "🚀 Full migration to Docker completed!"

db-mirror:
	@echo "🪞 Mirroring DB directly from host (3306) to Docker (3307)..."
	@echo "Ensure Docker database is running and accessible."
	mysqldump -h 127.0.0.1 -P 3306 -u root -p app | mysql -h 127.0.0.1 -P 3307 -u app -p"!ChangeMe!" app
	@echo "✅ Mirroring complete."

deploy-v2:
	@echo "🚀 Deploying v2 (Docker test stack)..."
	git pull
	$(MAKE) build
	$(MAKE) up
	$(MAKE) db-mirror
	$(MAKE) vendor
	$(MAKE) cc
	$(MAKE) db-migrate
	@echo "✅ v2 Deployment complete!"

# --- Docker ---
up:
	@echo "🚀 Starting services ($(ENV))..."
	$(DK_COMPOSE) up -d --remove-orphans
	@echo ""
	@echo "⏳ Waiting for services to be healthy..."
	@$(MAKE) _wait-healthy
	@echo ""
	@echo "✅ All services are up! Available at:"
	@$(MAKE) _print-links

_wait-healthy:
	@SERVICES=$$($(DK_COMPOSE) ps --services 2>/dev/null); \
	for SERVICE in $$SERVICES; do \
		if $(DK_COMPOSE) inspect --format='{{.Config.Healthcheck}}' $$SERVICE 2>/dev/null | grep -q 'CMD'; then \
			echo "  🔍 Checking $$SERVICE..."; \
			RETRIES=0; \
			MAX=30; \
			while [ $$RETRIES -lt $$MAX ]; do \
				STATUS=$$($(DK_COMPOSE) ps --format json 2>/dev/null | python3 -c "import sys,json; data=[json.loads(l) for l in sys.stdin if l.strip()]; svc=[s for s in data if s.get('Service','') == '$$SERVICE']; print(svc[0].get('Health','unknown') if svc else 'unknown')" 2>/dev/null || echo 'unknown'); \
				if [ "$$STATUS" = "healthy" ]; then \
					echo "  ✅ $$SERVICE: healthy"; \
					break; \
				fi; \
				if [ "$$STATUS" = "unhealthy" ]; then \
					echo "  ❌ $$SERVICE: UNHEALTHY — showing logs:"; \
					$(DK_COMPOSE) logs --tail=30 $$SERVICE; \
					echo ""; \
					echo "  ⚠️  Fix the issue above then run 'make up' again."; \
					exit 1; \
				fi; \
				RETRIES=$$((RETRIES+1)); \
				printf "  ⏳ $$SERVICE: $$STATUS ($$RETRIES/$$MAX)\r"; \
				sleep 2; \
			done; \
			if [ $$RETRIES -ge $$MAX ]; then \
				echo "  ⚠️  $$SERVICE: timed out waiting for healthy — showing logs:"; \
				$(DK_COMPOSE) logs --tail=30 $$SERVICE; \
				exit 1; \
			fi; \
		fi; \
	done

_print-links:
	@HTTP_PORT=$${PORT_APP_HTTP:-$${HTTP_PORT:-8095}}; \
	HTTPS_PORT=$${PORT_APP_HTTPS:-$${HTTPS_PORT:-8096}}; \
	DB_PORT=$${PORT_DATABASE:-5432}; \
	echo "   🌐 App (HTTP):       http://localhost:$$HTTP_PORT"; \
	echo "   🔒 App (HTTPS):      https://localhost:$$HTTPS_PORT"; \
	if [ "$(ENV)" = "dev" ] || [ "$(ENV)" = "" ]; then \
		echo "   📧 Mailpit (email):  http://localhost:8025"; \
	fi

down:
	$(DK_COMPOSE) down --remove-orphans

build:
	$(DK_COMPOSE) build

start: up

stop:
	$(DK_COMPOSE) stop

restart: stop start

logs:
	$(DK_COMPOSE) logs -f

ps:
	$(DK_COMPOSE) ps

sh:
	$(PHP_EXEC) sh

# --- Fixes & Caches ---

cc:
	$(SYMFONY) cache:clear

redis-flush:
	$(REDIS_CLI) FLUSHALL
	@echo "✅ Redis flushed."

refresh:
	@echo "🔄 Restarting PHP..."
	$(DK_COMPOSE) restart $(SERVICE_PHP)
	@echo "🧹 Flushing Redis..."
	-$(REDIS_CLI) FLUSHALL
	@echo "✨ Clearing Symfony Cache..."
	$(SYMFONY) cache:clear
	@echo "✅ Environment refreshed!"

# --- Composer ---
vendor:
	$(COMPOSER) install

# --- Database ---
db-migrate:
	$(SYMFONY) doctrine:migrations:migrate --no-interaction

db-diff:
	$(SYMFONY) doctrine:migrations:diff

# --- Deployment ---
deploy:
	@echo "🚀 Deploying..."
	git pull
	$(MAKE) build
	$(MAKE) up
	$(MAKE) vendor
	$(MAKE) cc
	$(MAKE) db-migrate

# --- Tests ---
tests:
	$(MAKE) up ENV=test
	$(DK_COMPOSE_CMD) -f compose.yaml --env-file .env.test exec --user root php composer install --no-interaction --no-progress --optimize-autoloader
	$(DK_COMPOSE_CMD) -f compose.yaml --env-file .env.test exec --user root php bin/phpunit
