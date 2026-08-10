<?php

declare(strict_types=1);

namespace Shared\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class ResolveCorrelationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->validUuid($request->headers->get('X-Request-ID')) ?? (string) Str::uuid();
        $correlationId = $this->validUuid($request->headers->get('X-Correlation-ID')) ?? $requestId;

        Context::add([
            'request_id' => $requestId,
            'correlation_id' => $correlationId,
        ]);

        $response = $next($request);

        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }

    private function validUuid(?string $value): ?string
    {
        if ($value !== null && Str::isUuid($value)) {
            return $value;
        }

        return null;
    }
}
