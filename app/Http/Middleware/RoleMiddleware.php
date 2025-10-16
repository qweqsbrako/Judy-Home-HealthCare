<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            return $this->unauthorizedResponse('Authentication required.');
        }

        // Check if user account is active
        if (!$user->is_active) {
            return $this->forbiddenResponse('Your account has been suspended. Please contact support.');
        }

        // Check if user is verified (for healthcare professionals)
        if ($user->is_healthcare_professional && !$user->is_verified) {
            return $this->forbiddenResponse('Your account is pending verification. Please wait for admin approval.');
        }

        // Check if user has required role
        if (!empty($roles) && !$user->hasAnyRole($roles)) {
            return $this->forbiddenResponse('You do not have permission to access this resource.');
        }

        return $next($request);
    }

    /**
     * Return unauthorized response.
     */
    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'UNAUTHORIZED'
        ], 401);
    }

    /**
     * Return forbidden response.
     */
    private function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'FORBIDDEN'
        ], 403);
    }
}