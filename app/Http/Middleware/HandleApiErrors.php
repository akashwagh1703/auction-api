<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class HandleApiErrors
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Resource not found',
                'error' => class_basename($e->getModel()) . ' not found',
            ], 404);
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'Please login to access this resource',
            ], 401);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'Forbidden',
                'error' => 'You do not have permission to perform this action',
            ], 403);
        } catch (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $e) {
            return response()->json([
                'message' => 'Too many requests',
                'error' => 'Please wait before trying again',
                'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
            ], 429);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('API Error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);

            // Return generic error in production, detailed in development
            if (config('app.debug')) {
                return response()->json([
                    'message' => 'Server error',
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => collect($e->getTrace())->map(fn ($trace) => Arr::except($trace, ['args']))->all(),
                ], 500);
            }

            return response()->json([
                'message' => 'Server error',
                'error' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }
}
