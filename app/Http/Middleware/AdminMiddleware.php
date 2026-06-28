<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    use ApiResponse;

    public function handle(Request $request, Closure $next, string $role = 'admin'): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        if ($role === 'super_admin' && !$user->isSuperAdmin()) {
            return $this->forbiddenResponse('Only Super Admins can perform this action.');
        }

        if ($role === 'admin' && !$user->isAdmin()) {
            return $this->forbiddenResponse('Admin access required.');
        }

        return $next($request);
    }
}
