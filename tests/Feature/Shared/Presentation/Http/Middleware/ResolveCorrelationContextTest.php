<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

it('generates a request id and correlation id when no headers are supplied', function () {
    $response = $this->postJson(route('identity.auth.register'), []);

    $requestId = $response->headers->get('X-Request-ID');
    $correlationId = $response->headers->get('X-Correlation-ID');

    expect(Str::isUuid($requestId))->toBeTrue()
        ->and(Str::isUuid($correlationId))->toBeTrue()
        ->and($correlationId)->toBe($requestId);
});

it('keeps a valid supplied request id and defaults correlation id to it', function () {
    $requestId = (string) Str::uuid();

    $response = $this->postJson(route('identity.auth.register'), [], [
        'X-Request-ID' => $requestId,
    ]);

    expect($response->headers->get('X-Request-ID'))->toBe($requestId)
        ->and($response->headers->get('X-Correlation-ID'))->toBe($requestId);
});

it('keeps valid supplied request id and correlation id', function () {
    $requestId = (string) Str::uuid();
    $correlationId = (string) Str::uuid();

    $response = $this->postJson(route('identity.auth.register'), [], [
        'X-Request-ID' => $requestId,
        'X-Correlation-ID' => $correlationId,
    ]);

    expect($response->headers->get('X-Request-ID'))->toBe($requestId)
        ->and($response->headers->get('X-Correlation-ID'))->toBe($correlationId);
});

it('replaces invalid request id and correlation id headers', function () {
    $response = $this->postJson(route('identity.auth.register'), [], [
        'X-Request-ID' => 'not-a-uuid',
        'X-Correlation-ID' => str_repeat('a', 5000),
    ]);

    $requestId = $response->headers->get('X-Request-ID');
    $correlationId = $response->headers->get('X-Correlation-ID');

    expect(Str::isUuid($requestId))->toBeTrue()
        ->and($requestId)->not->toBe('not-a-uuid')
        ->and(Str::isUuid($correlationId))->toBeTrue()
        ->and($correlationId)->not->toBe(str_repeat('a', 5000));
});

it('shares the resolved ids with the log context', function () {
    $requestId = (string) Str::uuid();

    $response = $this->postJson(route('identity.auth.register'), [], [
        'X-Request-ID' => $requestId,
    ]);

    expect(Context::get('request_id'))->toBe($requestId)
        ->and(Context::get('correlation_id'))->toBe($requestId)
        ->and($response->headers->get('X-Request-ID'))->toBe($requestId);
});
