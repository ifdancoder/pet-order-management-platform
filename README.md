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
docker compose exec app php artisan test --compact
```

The default Pest suites use an in-memory SQLite database.

## Identity API

Generate a new JWT key pair only when rotating local keys:

```bash
docker compose exec app php artisan identity:generate-jwt-keys --force
```

Identity routes are available below `/api/v1/identity`. Access tokens are signed RS256 JWTs. Refresh tokens are opaque, stored as hashes, and rotated on use.
