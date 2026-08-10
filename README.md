# OrderFlow

OrderFlow is an internal order processing and fulfillment platform implemented as a Laravel modular monolith. It exposes a JSON HTTP API for customer accounts, orders, payments, and returns, and coordinates fulfillment work (payments, shipments, notifications) through an internal event bus backed by RabbitMQ.

## Features

- JWT-based authentication with access and refresh tokens, and a user lifecycle (pending, active, suspended, disabled).
- Customer profiles with delivery addresses, linked to Identity by user ID only.
- Inventory reservations with row-level locking and idempotent reservation keys.
- Order lifecycle management: draft creation, item changes, placement, and fulfillment transitions.
- Checkout-time promotion evaluation (percentage and fixed discounts with eligibility rules).
- Payment requests against orders, with Stripe and PayPal webhook processing.
- Asynchronous refund coordination between Return and Payment via a transactional outbox and RabbitMQ.
- Shipment booking once payment is captured.
- Email notifications on domain events (for example, user registration).
- Return requests with eligibility checks (order status, return window, purchased quantities).
- Request/correlation IDs propagated from HTTP requests through the command bus, outbox, and RabbitMQ consumers into structured logs.

## Architecture

The codebase is a modular monolith: one Laravel application, several independently structured business modules under `src/Modules`, plus cross-cutting code under `src/Shared`.

Each module is split into four layers with a fixed dependency direction:

```
Presentation -> Application -> Domain
Infrastructure -> Application -> Domain
```

- `Domain` holds entities, value objects, and domain services. It does not depend on Illuminate or on any other layer, including its own module's Application layer.
- `Application` holds commands, queries, their handlers, and the ports (interfaces) those handlers depend on. It does not depend on Infrastructure or Presentation.
- `Infrastructure` implements the ports: Eloquent repositories, outbound gateways, queue jobs, RabbitMQ adapters. Eloquent models are a persistence detail confined to this layer.
- `Presentation` holds HTTP controllers, Form Requests, and API Resources. Controllers dispatch commands and queries through the shared buses; they do not contain business logic.

Modules do not read another module's Eloquent models or database tables. Cross-module reads go through an explicit port defined by the *consuming* module's Application layer (for example `IOrderReturnLookup`, `ICustomerIdentityLookup`, `IReturnOrderGateway`) and implemented in Infrastructure by calling the other module's own commands or queries. `Shared` never depends on `Modules`. These rules are enforced by Pest architecture tests (`tests/Unit/*ArchitectureTest.php`).

Cross-module facts that need to reach another module asynchronously (a captured payment, a received return) are published as integration events through a transactional outbox and consumed from RabbitMQ, rather than called synchronously in-process.

## Modules

| Module | Responsibility |
| --- | --- |
| Identity | User accounts, JWT access/refresh tokens, user status lifecycle. |
| Customer | Customer profile and delivery addresses. |
| Inventory | Stock items and reservations. |
| Order | Order lifecycle and checkout orchestration across Customer, Inventory, Promotion, and Shipping. |
| Promotion | Discount codes and eligibility rules applied during checkout. |
| Payment | Payment requests, Stripe/PayPal webhooks, refund coordination. |
| Shipping | Shipment booking and cost calculation. |
| Return | Return requests, eligibility, and refund coordination with Payment. |
| Notification | Outbound email notifications triggered by domain events. |

Order, Inventory, Shipping, Notification, and Promotion expose internal command/query contracts only; they have no public HTTP routes. Identity, Customer, Payment, and Return expose the HTTP API described below.

## Tech Stack

- PHP 8.3+ (the Docker image runs PHP 8.5)
- Laravel 13
- PostgreSQL (application database and integration test target)
- SQLite in memory (default test database for unit and feature tests)
- Redis (cache and session store)
- RabbitMQ (integration event transport)
- `lcobucci/jwt` (access/refresh token signing)
- `php-amqplib/php-amqplib` (RabbitMQ client)
- Docker Compose (local environment)
- Pest / PHPUnit
- PHPStan via Larastan (level 7)
- Laravel Pint

## Project Structure

```
src/
├── Modules/
│   └── <ModuleName>/
│       ├── Domain/
│       ├── Application/
│       ├── Infrastructure/
│       └── Presentation/
└── Shared/
    ├── Application/   # command/query bus contracts, outbox, messaging contracts
    ├── Infrastructure/ # bus implementation, RabbitMQ adapters, outbox persistence
    └── Presentation/   # cross-cutting HTTP concerns (e.g. correlation middleware)
```

Every module under `src/Modules` follows the same four-layer split described in Architecture. `src/Shared` provides the command/query bus, the outbox, and RabbitMQ messaging infrastructure that every module builds on; it contains no business rules.

## Getting Started

### Requirements

- Docker with Docker Compose
- Git

### Installation

```bash
cp .env.example .env
docker compose build app
docker compose run --rm app php artisan key:generate
```

### Environment

The development PostgreSQL and RabbitMQ credentials are `orderflow` / `orderflow`. Override published ports or the Docker subnet when they conflict with other local services:

```bash
APP_PORT=8080 POSTGRES_PORT=55432 ORDERFLOW_SUBNET=172.31.241.0/24 docker compose up --detach
```

### Database

Migrations run automatically when the `app` container starts. The application container also creates the local JWT key pair when it is missing.

### Running the Application

```bash
docker compose up --detach
docker compose ps
```

Local endpoints:

- API: `http://localhost:8000`
- RabbitMQ management: `http://localhost:15672`
- PostgreSQL: `localhost:5432`
- Redis: `localhost:6379`

Stop containers without deleting database or broker data:

```bash
docker compose down
```

Generate a new JWT key pair only when rotating local keys:

```bash
docker compose exec app php artisan identity:generate-jwt-keys --force
```

### Workers

Docker Compose starts one container per background responsibility:

| Service | Role |
| --- | --- |
| `queue-worker` | Runs `queue:work` against the `outbox`, `notifications`, `shipping`, `refunds`, and `default` queues. |
| `scheduler` | Runs `schedule:work`, which dispatches the outbox publisher and the refund/shipment/notification dispatch jobs every second. |
| `message-consumer` | Consumes `order-payment-status` (Order reacts to captured/failed payments). |
| `notification-consumer` | Consumes `notification-user-registered`. |
| `shipping-consumer` | Consumes `shipping-payment-captured`. |
| `payment-return-consumer` | Consumes `payment-return-received` (Payment reacts to a received return). |
| `return-consumer` | Consumes `return-payment-refunded` (Return reacts to a completed refund). |

Application code dispatches integration events into a database-backed outbox inside the same transaction as the business change. The outbox publisher and the RabbitMQ consumers run in separate processes so a broker outage cannot roll back a committed transaction.

## API

The HTTP API is versioned under `/api/v1`. Authenticated routes require `Authorization: Bearer <access_token>`.

| Method | Path | Module | Auth |
| --- | --- | --- | --- |
| POST | `/api/v1/identity/auth/register` | Identity | - |
| POST | `/api/v1/identity/auth/login` | Identity | - |
| POST | `/api/v1/identity/auth/refresh` | Identity | - |
| POST | `/api/v1/identity/auth/logout` | Identity | - |
| GET | `/api/v1/identity/users/{userId}` | Identity | Bearer |
| PATCH | `/api/v1/identity/users/{userId}` | Identity | Bearer |
| DELETE | `/api/v1/identity/users/{userId}` | Identity | Bearer |
| POST | `/api/v1/identity/users/{userId}/activate` | Identity | Bearer |
| POST | `/api/v1/identity/users/{userId}/suspend` | Identity | Bearer |
| POST | `/api/v1/identity/users/{userId}/restore` | Identity | Bearer |
| POST | `/api/v1/customers/profile` | Customer | Bearer |
| GET | `/api/v1/customers/profile` | Customer | Bearer |
| PATCH | `/api/v1/customers/profile` | Customer | Bearer |
| POST | `/api/v1/customers/profile/addresses` | Customer | Bearer |
| PUT | `/api/v1/customers/profile/addresses/{addressId}` | Customer | Bearer |
| DELETE | `/api/v1/customers/profile/addresses/{addressId}` | Customer | Bearer |
| POST | `/api/v1/customers/profile/addresses/{addressId}/default` | Customer | Bearer |
| POST | `/api/v1/orders/{orderId}/payments` | Payment | Bearer |
| POST | `/api/v1/payments/webhooks/{provider}` | Payment | Provider signature |
| POST | `/api/v1/returns` | Return | Bearer |
| GET | `/api/v1/returns/{returnId}` | Return | Bearer |

The full request/response contract, including validation rules, error codes, and headers, is in [`openapi.yaml`](openapi.yaml). A Russian translation of this README is available in [`README.ru.md`](README.ru.md).

Every response carries `X-Request-ID` and `X-Correlation-ID` headers. Supplying a valid UUID in the request headers of the same names preserves it; otherwise the server generates one.

## Testing

```bash
docker compose exec app php artisan test --compact
docker compose exec app composer test:integration
```

Unit, feature, and architecture tests run against an in-memory SQLite database. The integration suite runs against the dedicated `orderflow_test` PostgreSQL database and a real RabbitMQ instance; it verifies PostgreSQL-specific behavior such as row locking under concurrent access and end-to-end message delivery. It requires `RABBITMQ_USER=orderflow` and `RABBITMQ_PASSWORD=orderflow` in the environment.

## Static Analysis

```bash
docker compose exec app composer analyse
```

PHPStan (via Larastan) runs at level 7 against `app`, `bootstrap`, `config`, `database`, and `src`.

## Code Style

```bash
docker compose exec app vendor/bin/pint --format agent
docker compose exec app composer validate --strict
```

## Development

CI (`.github/workflows/ci.yml`) runs Composer validation, Pint, static analysis, the SQLite test suite, and the PostgreSQL integration suite on every push and pull request.

Command and query handlers run through shared buses (`ICommandBus`, `IQueryBus`). The bus does not open database transactions; transaction boundaries stay explicit inside application handlers so an external HTTP call (a payment gateway, a shipping provider) is never made inside an open transaction.

## Roadmap

Metrics and distributed tracing are not implemented. Structured logging and request/correlation ID propagation are in place; adding metrics would require agreeing on an exporter and backend first.

## License

MIT, per `composer.json`.
