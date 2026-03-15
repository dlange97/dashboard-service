# Copilot Instructions - Dashboard Service

Scope: This repository only (my-dashboard-backend/dashboard-service).

## Stack
- PHP 8.2+, Symfony, Doctrine.

## Rules
- Keep API contracts stable and explicit.
- Keep domain logic in services, not controllers.
- Use typed DTOs and guard clauses.
- Update OpenAPI/Swagger docs for endpoint changes.
- Avoid direct dependencies on other services' databases.

## Quality
- Run service tests after changes: docker compose exec dashboard-php bin/phpunit.
- For entity changes, keep migrations deterministic and reviewed.
