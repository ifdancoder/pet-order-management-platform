# OrderFlow

OrderFlow is an internal order processing and fulfillment platform implemented as a Laravel modular monolith.

## Local environment

Requirements:

- Docker with Docker Compose
- Git

Create the local environment file and application key:

```bash
cp .env.example .env
docker compose build app
docker compose run --rm app php artisan key:generate
```

Start the application and infrastructure:

```bash
docker compose up --detach
docker compose ps
```

The application container installs Composer dependencies, runs migrations, and creates the local JWT key pair when it is missing.

Local endpoints:

- API: `http://localhost:8000`
- RabbitMQ management: `http://localhost:15672`
- PostgreSQL: `localhost:5432`
- Redis: `localhost:6379`

The development PostgreSQL and RabbitMQ credentials are `orderflow` / `orderflow`. Override published ports or the Docker subnet when they conflict with other local services:

```bash
APP_PORT=8080 POSTGRES_PORT=55432 ORDERFLOW_SUBNET=172.31.241.0/24 docker compose up --detach
```

Stop containers without deleting database or broker data:

```bash
docker compose down
```

## Quality checks

Run checks inside the application container:

```bash
docker compose exec app composer validate --strict
docker compose exec app vendor/bin/pint --format agent
docker compose exec app composer analyse
docker compose exec app php artisan test --compact
docker compose exec app composer test:integration
```

The default Pest suites use an in-memory SQLite database. The integration suite uses the dedicated `orderflow_test` PostgreSQL database and verifies PostgreSQL-specific constraints and row locking.

## Continuous integration

GitHub Actions runs Composer validation, Pint, Larastan, the SQLite test suite, and the PostgreSQL integration suite on pushes and pull requests. PostgreSQL is the only CI service currently required by implemented integration tests.

## Application buses

HTTP controllers dispatch typed commands and queries through the shared synchronous buses. Each module registers its handler mappings in its own service provider. Command middleware logs command class names and outcomes without serializing command payloads.

The bus does not open database transactions. Transaction boundaries remain explicit inside application handlers so external network calls are not accidentally executed inside a transaction.

## Identity API

Generate a new JWT key pair only when rotating local keys:

```bash
docker compose exec app php artisan identity:generate-jwt-keys --force
```

Identity routes are available below `/api/v1/identity`. Access tokens are signed RS256 JWTs. Refresh tokens are opaque, stored as hashes, and rotated on use.

## Customer API

Authenticated customer profile routes are available below `/api/v1/customers/profile`. The JWT identity is passed to Customer as an ID only; Customer does not query Identity tables or use cross-module Eloquent relationships.

The profile API supports profile creation, retrieval and updates, plus adding, updating, selecting and removing delivery addresses. The first address becomes the default automatically, and the database prevents more than one default address per customer.

## Inventory reservations

Inventory exposes synchronous command/query contracts for item creation, restocking, reservation and release. A reservation may contain multiple inventory items and uses a caller-supplied reservation key for idempotency.

Reservation handlers lock inventory rows in ascending ID order inside a PostgreSQL transaction. Stock constraints prevent negative quantities and reservations above on-hand stock. The integration suite runs competing PHP processes against PostgreSQL to prove that only one process can reserve the final unit and that concurrent retries with the same key apply once.
