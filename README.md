# dashboard-service

## Overview

Dashboard domain microservice (todos and shopping lists).

## Contents

- `src/` — API endpoints and business logic.
- `migrations/` — database migrations.
- `tests/` — PHPUnit test suite.

## Run (in stack)

```bash
docker compose -f ../../my-dashboard-docker/docker-compose.yml up -d dashboard-php
```

## Common Operations

```bash
# Migrations
docker compose -f ../../my-dashboard-docker/docker-compose.yml exec -T dashboard-php php bin/console doctrine:migrations:migrate --no-interaction

# Tests
docker compose -f ../../my-dashboard-docker/docker-compose.yml exec -T dashboard-php php bin/phpunit

# Quality checks + coverage gate
docker compose -f ../../my-dashboard-docker/docker-compose.yml exec -T dashboard-php composer run quality
```

Run quality checks after every code change in this service.
