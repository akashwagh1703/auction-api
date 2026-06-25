<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log incoming request
        $this->logRequest($request);

        $response = $next($request);

        // Log outgoing response
        $this->logResponse($request, $response, $startTime);

        return $response;
    }

    protected function logRequest(Request $request): void
    {
        $data = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
        ];

        // Only log request body for non-GET requests and exclude sensitive data
        if ($request->method() !== 'GET') {
            $data['body'] = $this->sanitizeData($request->all());
        }

        Log::info('API Request', $data);
    }

    protected function logResponse(Request $request, Response $response, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2); // in milliseconds

        $data = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => auth()->id(),
        ];

        // Log slow requests (> 1 second)
        if ($duration > 1000) {
            Log::warning('Slow API Request', $data);
        } else {
            Log::info('API Response', $data);
        }
    }

    protected function sanitizeData(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'current_password', 'token', 'api_key', 'secret'];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeData($value);
            } elseif (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '********';
            }
        }

        return $data;
    }
}
