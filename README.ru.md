# OrderFlow

*[English version](README.md)*

OrderFlow - внутренняя платформа обработки заказов и фулфилмента, реализованная как модульный монолит на Laravel. Она предоставляет JSON HTTP API для учётных записей клиентов, заказов, платежей и возвратов, а координацию фулфилмента (платежи, отгрузки, уведомления) выполняет через внутреннюю шину событий поверх RabbitMQ.

## Возможности

- JWT-аутентификация с access и refresh токенами, жизненный цикл пользователя (pending, active, suspended, disabled).
- Профили клиентов с адресами доставки, связанные с Identity только по идентификатору пользователя.
- Резервирование остатков с блокировкой строк на уровне БД и идемпотентными ключами резервирования.
- Управление жизненным циклом заказа: создание черновика, изменение позиций, оформление, переходы фулфилмента.
- Применение промоакций на этапе оформления заказа (процентные и фиксированные скидки с правилами применимости).
- Запросы на оплату по заказу, обработка webhook от Stripe и PayPal.
- Асинхронная координация возвратов денег между Return и Payment через транзакционный outbox и RabbitMQ.
- Бронирование отгрузки после захвата платежа.
- Email-уведомления по доменным событиям (например, регистрация пользователя).
- Запросы на возврат товара с проверкой применимости (статус заказа, окно возврата, купленное количество).
- Request/correlation ID, которые передаются от HTTP-запроса через command bus, outbox и консьюмеров RabbitMQ в структурированные логи.

## Технологический стек

- PHP 8.3+ (Docker-образ использует PHP 8.5)
- Laravel 13
- PostgreSQL (база данных приложения и цель integration-тестов)
- SQLite in memory (база по умолчанию для unit- и feature-тестов)
- Redis (кеш и хранилище сессий)
- RabbitMQ (транспорт интеграционных событий)
- `lcobucci/jwt` (подпись access/refresh токенов)
- `php-amqplib/php-amqplib` (клиент RabbitMQ)
- Docker Compose (локальное окружение)
- Pest / PHPUnit
- PHPStan через Larastan (уровень 7)
- Laravel Pint

## Ключевые инженерные решения

### Архитектура модульного монолита

Кодовая база - модульный монолит: одно приложение Laravel, несколько независимо структурированных бизнес-модулей в `src/Modules`, и сквозной код в `src/Shared`.

Каждый модуль разделён на четыре слоя с фиксированным направлением зависимостей:

```
Presentation -> Application -> Domain
Infrastructure -> Application -> Domain
```

- `Domain` содержит сущности, value object и доменные сервисы. Он не зависит от Illuminate и от других слоёв, включая Application того же модуля.
- `Application` содержит команды, запросы, их обработчики и порты (интерфейсы), от которых эти обработчики зависят. Он не зависит от Infrastructure и Presentation.
- `Infrastructure` реализует порты: Eloquent-репозитории, исходящие шлюзы, queue jobs, адаптеры RabbitMQ. Eloquent-модели - деталь хранения, ограниченная этим слоем.
- `Presentation` содержит HTTP-контроллеры, Form Request и API Resource. Контроллеры отправляют команды и запросы через общие шины; бизнес-логики в них нет.

Модули не читают Eloquent-модели или таблицы БД другого модуля. Межмодульное чтение идёт через явный порт, объявленный в Application-слое *потребляющего* модуля (например, `IOrderReturnLookup`, `ICustomerIdentityLookup`, `IReturnOrderGateway`) и реализованный в Infrastructure вызовом собственных команд или запросов другого модуля. `Shared` никогда не зависит от `Modules`. Эти правила проверяются architecture-тестами Pest (`tests/Unit/*ArchitectureTest.php`).

Факты, которые должны асинхронно дойти до другого модуля (захваченный платёж, полученный возврат), публикуются как интеграционные события через транзакционный outbox и потребляются из RabbitMQ, а не вызываются синхронно внутри процесса.

### Паттерны проектирования

Паттерны применены как именованные GoF/enterprise-паттерны, а не просто "похожая идея":

| Паттерн | Где | Пример |
| --- | --- | --- |
| Command | `Application/Command` в каждом модуле | `RequestReturnCommand` + `RequestReturnHandler` |
| Query | `Application/Query` в каждом модуле | `GetReturnQuery` + `GetReturnHandler` |
| Mediator (Command/Query Bus) | `Shared\Infrastructure\Bus` | `LaravelCommandBus`, `LaravelQueryBus` |
| Middleware pipeline | `LaravelCommandBus` | `array_reduce` по `ICommandMiddleware[]`, сейчас это `LoggingCommandMiddleware` |
| Decorator | `Shared\Infrastructure\Bus\Query\LoggingQueryBus` | Оборачивает `IQueryBus`, добавляет логирование, не меняя интерфейс |
| Repository | `Application/Port/Out/Persistence` в каждом модуле + Eloquent-реализация | `IOrderRepository` / `EloquentOrderRepository` |
| Mapper | `Infrastructure/Adapter/Out/Persistence/Eloquent/Mapper` в каждом модуле | `PromotionMapper` конвертирует между Eloquent-моделью и доменной сущностью |
| Adapter | `Infrastructure/Adapter/Out` в каждом модуле | `RabbitMqMessagePublisher`, `StripePaymentGateway` |
| Strategy | Shipping | `CourierShippingStrategy`, `ExpressShippingStrategy`, `InternationalShippingStrategy`, `PickupPointShippingStrategy` за `IShippingCostStrategy` |
| Factory / Resolver | Payment, Shipping, Notification | `PaymentGatewayResolver`, `ShippingStrategyResolver`, `NotificationChannelResolver` выбирают конкретную реализацию по enum/ключу |
| Specification | Return, Promotion | `WithinReturnWindowSpecification`, `DateRangeSpecification`, комбинируются через `AndReturnEligibilitySpecification` / `AndSpecification` |
| Composite | Promotion | `CompositeDiscount` объединяет несколько `IDiscountPolicy` |

Три вещи из таблицы выше на первый взгляд похожи на паттерн, но реализованы проще, намеренно:

- **Статусы Order, Payment, Return - это не GoF State.** У каждого есть backed enum и метод-guard (`transition($expected, $target)`) на сущности, который бросает исключение при недопустимом переходе. Отдельных полиморфных классов состояний нет: guard в виде конечного автомата оказался достаточен и не добавляет слой косвенности, который больше нигде не нужен.
- **Валидация checkout - это rule pipeline, а не Chain of Responsibility.** `Modules\Order\Application\Checkout\CheckoutRulePipeline` последовательно выполняет плоский `iterable<ICheckoutRule>` (`OrderNotEmptyRule`, `CustomerCanOrderRule`, `InventoryAvailableRule`, `ApplyPromotionsRule`, `CalculateShippingRule`). Каждое правило независимо, ни одно не решает, вызывать ли следующее, поэтому настоящей цепочки нет.
- **In-process Observer/domain-event диспетчера нет.** Нигде в `src` не используется `Event`/листенеры Laravel. Любая реакция на бизнес-факт пересекает границу модуля только через outbox → RabbitMQ → consumer, описанный выше, а это publish/subscribe на границе процессов, а не классический Subject/Observer.

### Заметные детали реализации

- **Идемпотентность**: запрос на оплату принимает клиентский заголовок `Idempotency-Key`; повтор с тем же ключом возвращает исходный платёж, а не создаёт второй. Возвраты денег идентифицируются `refund_id` сквозным образом, а `processed_messages` (consumer, message_id) делает обработку сообщений RabbitMQ идемпотентной независимо от повторов и передоставки.
- **Доставка через outbox**: строки `outbox_messages` захватываются через `FOR UPDATE SKIP LOCKED` (PostgreSQL), поэтому несколько процессов publisher никогда не опубликуют одну и ту же строку дважды; у захвата есть токен и срок, и непубликованный захват автоматически освобождается после истечения таймаута.
- **Конкурентность в Inventory**: резервирование блокирует нужные строки остатков в порядке возрастания ID внутри одной транзакции - именно это предотвращает deadlock при конкурентных резервированиях, что проверяется integration-тестом, запускающим конкурирующие подключения к PostgreSQL на один и тот же остаток.
- **Передача correlation**: `X-Request-ID`/`X-Correlation-ID` проходят от HTTP middleware через log context command bus, попадают в строку outbox, в нативное свойство `correlation_id` сообщения RabbitMQ и обратно в log context консьюмера, не затрагивая бизнес-payload. Старые строки outbox и сообщения без этих метаданных по-прежнему корректно декодируются.
- **Граница транзакции заканчивается на процессе, а не на сети**: транзакцию открывает application-обработчик; сам HTTP-вызов к платёжному шлюзу или службе доставки всегда выполняется вне неё, поэтому медленный или падающий внешний сервис не может удерживать блокировку в БД.

## Структура проекта

```
src/
├── Modules/
│   └── <ModuleName>/
│       ├── Domain/
│       ├── Application/
│       ├── Infrastructure/
│       └── Presentation/
└── Shared/
    ├── Application/   # контракты command/query bus, outbox, messaging
    ├── Infrastructure/ # реализация bus, адаптеры RabbitMQ, хранение outbox
    └── Presentation/   # сквозные HTTP-механизмы (например, correlation middleware)
```

Каждый модуль в `src/Modules` следует одному и тому же четырёхслойному делению, описанному выше. `src/Shared` предоставляет command/query bus, outbox и messaging-инфраструктуру RabbitMQ, на которой строится каждый модуль; бизнес-правил в нём нет.

| Модуль | Ответственность |
| --- | --- |
| Identity | Учётные записи пользователей, JWT access/refresh токены, жизненный цикл статуса пользователя. |
| Customer | Профиль клиента и адреса доставки. |
| Inventory | Складские позиции и резервирования. |
| Order | Жизненный цикл заказа и оркестрация оформления между Customer, Inventory, Promotion и Shipping. |
| Promotion | Промокоды и правила применимости при оформлении заказа. |
| Payment | Запросы на оплату, webhook Stripe/PayPal, координация возвратов денег. |
| Shipping | Бронирование отгрузки и расчёт стоимости доставки. |
| Return | Запросы на возврат товара, проверка применимости, координация возврата денег с Payment. |
| Notification | Исходящие email-уведомления по доменным событиям. |

Order, Inventory, Shipping, Notification и Promotion предоставляют только внутренние command/query контракты; публичных HTTP-маршрутов у них нет. Identity, Customer, Payment и Return предоставляют HTTP API, описанный ниже.

## Как запустить

### Требования

- Docker с Docker Compose
- Git

### Установка

```bash
cp .env.example .env
docker compose build app
docker compose run --rm app php artisan key:generate
```

### Запуск стека

```bash
docker compose up --detach
docker compose ps
```

Локальные адреса:

- API: `http://localhost:8000`
- RabbitMQ management: `http://localhost:15672`
- PostgreSQL: `localhost:5432`
- Redis: `localhost:6379`

При конфликте портов или подсети Docker с другими локальными сервисами переопределите их:

```bash
APP_PORT=8080 POSTGRES_PORT=55432 ORDERFLOW_SUBNET=172.31.241.0/24 docker compose up --detach
```

Остановить контейнеры без удаления данных БД и брокера:

```bash
docker compose down
```

### Фоновые воркеры

Docker Compose поднимает по одному контейнеру на каждую фоновую задачу:

| Сервис | Роль |
| --- | --- |
| `queue-worker` | Выполняет `queue:work` для очередей `outbox`, `notifications`, `shipping`, `refunds`, `default`. |
| `scheduler` | Выполняет `schedule:work`, который каждую секунду запускает публикацию outbox и job'ы диспетчеризации возвратов, отгрузок и уведомлений. |
| `message-consumer` | Читает `order-payment-status` (Order реагирует на захваченные/неуспешные платежи). |
| `notification-consumer` | Читает `notification-user-registered`. |
| `shipping-consumer` | Читает `shipping-payment-captured`. |
| `payment-return-consumer` | Читает `payment-return-received` (Payment реагирует на полученный возврат). |
| `return-consumer` | Читает `return-payment-refunded` (Return реагирует на завершённый возврат денег). |

Код приложения записывает интеграционные события в outbox в базе данных в той же транзакции, что и бизнес-изменение. Publisher outbox и консьюмеры RabbitMQ работают в отдельных процессах, поэтому сбой брокера не может откатить уже закоммиченную транзакцию.

## Переменные окружения и секреты

Docker Compose сам подставляет локальные значения по умолчанию для БД, брокера и JWT-ключей, поэтому на свежем клоне `docker compose up` работает без ручной настройки секретов. Переменные ниже важны, только когда вы выходите за рамки локального стека по умолчанию:

| Переменная | Назначение | Где взять |
| --- | --- | --- |
| `APP_KEY` | Ключ шифрования приложения Laravel | Генерируется локально командой `php artisan key:generate`, внешний источник не нужен |
| `JWT_PRIVATE_KEY_PATH` / `JWT_PUBLIC_KEY_PATH` | Собственная пара RSA-ключей для подписи access/refresh токенов | Опционально; если не задано, контейнер `app` автоматически генерирует локальную пару |
| `STRIPE_SECRET_KEY` / `STRIPE_WEBHOOK_SECRET` | Обработка платежей Stripe и проверка подписи webhook | Stripe Dashboard → Developers → API keys / Webhooks |
| `PAYPAL_CLIENT_ID` / `PAYPAL_CLIENT_SECRET` / `PAYPAL_WEBHOOK_ID` | Обработка платежей PayPal и проверка webhook | PayPal Developer Dashboard → учётные данные вашего приложения |
| `RABBITMQ_USER` / `RABBITMQ_PASSWORD` | Учётные данные брокера RabbitMQ | По умолчанию `orderflow` / `orderflow` в Docker Compose; задайте явно при подключении к внешнему брокеру |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | Подключение к PostgreSQL, если не используется встроенный сервис `postgres` | Ваш экземпляр PostgreSQL |

Полный список настраиваемых параметров (таймауты, размеры батчей, имена очередей) - в `.env.example`; у них рабочие значения по умолчанию, и для локальной разработки менять их почти никогда не нужно.

## Документация API

HTTP API версионирован под `/api/v1`. Для защищённых маршрутов требуется `Authorization: Bearer <access_token>`.

| Метод | Путь | Модуль | Аутентификация |
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
| POST | `/api/v1/payments/webhooks/{provider}` | Payment | Подпись провайдера |
| POST | `/api/v1/returns` | Return | Bearer |
| GET | `/api/v1/returns/{returnId}` | Return | Bearer |

Полный контракт запросов/ответов, включая правила валидации, коды ошибок и заголовки, находится в [`openapi.yaml`](openapi.yaml).

Каждый ответ содержит заголовки `X-Request-ID` и `X-Correlation-ID`. Если в запросе передан валидный UUID в заголовке с тем же именем, он сохраняется; иначе сервер генерирует новый.

### Примеры запросов

Регистрация пользователя (создаётся в статусе `pending`, пара токенов не возвращается):

```bash
curl -X POST http://localhost:8000/api/v1/identity/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email": "jane@example.com", "password": "Str0ng!Passw0rd123"}'
```

Вход, чтобы получить пару access/refresh токенов:

```bash
curl -X POST http://localhost:8000/api/v1/identity/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "jane@example.com", "password": "Str0ng!Passw0rd123"}'
```

```json
{
  "data": {
    "access_token": "eyJhbGciOi...",
    "refresh_token": "eyJhbGciOi...",
    "token_type": "Bearer",
    "access_expires_at": "2026-10-01T12:15:00Z",
    "refresh_expires_at": "2026-10-31T12:00:00Z"
  }
}
```

Вызов защищённого маршрута с access-токеном:

```bash
curl http://localhost:8000/api/v1/customers/profile \
  -H "Authorization: Bearer eyJhbGciOi..."
```

## Миграции базы данных

Миграции лежат в `database/migrations`. Контейнер `app` автоматически выполняет `php artisan migrate --force` при старте, поэтому после каждого `docker compose up` схема БД актуальна.

Чтобы создать или применить миграции вручную:

```bash
docker compose exec app php artisan make:migration create_something_table
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:rollback
```

## Тестирование

```bash
docker compose exec app php artisan test --compact
docker compose exec app composer test:integration
```

Unit-, feature- и architecture-тесты выполняются на in-memory SQLite. Integration-набор выполняется на выделенной PostgreSQL-базе `orderflow_test` и реальном RabbitMQ; он проверяет специфичное для PostgreSQL поведение, например блокировку строк при конкурентном доступе, и сквозную доставку сообщений. Для него нужны переменные окружения `RABBITMQ_USER=orderflow` и `RABBITMQ_PASSWORD=orderflow`.

## Качество кода

```bash
docker compose exec app composer analyse
docker compose exec app vendor/bin/pint --format agent
docker compose exec app composer validate --strict
```

PHPStan (через Larastan) запускается на уровне 7 для `app`, `bootstrap`, `config`, `database` и `src`. CI (`.github/workflows/ci.yml`) на каждый push и pull request запускает валидацию Composer, Pint, статический анализ, набор тестов на SQLite и integration-набор на PostgreSQL.

Обработчики команд и запросов выполняются через общие шины (`ICommandBus`, `IQueryBus`). Шина не открывает транзакции БД: границы транзакций остаются явными внутри application-обработчиков, чтобы внешний HTTP-вызов (платёжный шлюз, служба доставки) никогда не выполнялся внутри открытой транзакции.

## Ограничения

Метрики и распределённый трейсинг не реализованы. Структурированное логирование и передача request/correlation ID уже есть; для метрик сначала нужно согласовать exporter и backend.

## Лицензия

MIT, согласно `composer.json`.
