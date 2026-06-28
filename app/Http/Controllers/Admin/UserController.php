<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/admin/users
     * List all users with optional filters
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::with('wallet')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('first_name', 'like', "%{$request->search}%")
                          ->orWhere('last_name', 'like', "%{$request->search}%")
                          ->orWhere('email', 'like', "%{$request->search}%")
                          ->orWhere('account_number', 'like', "%{$request->search}%")
                          ->orWhere('phone', 'like', "%{$request->search}%");
                });
            })
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->status, function ($q) use ($request) {
                $q->where('is_active', $request->status === 'active');
            })
            ->latest()
            ->paginate($request->get('per_page', 20));

        return $this->paginatedResponse($users, 'Users retrieved successfully');
    }

    /**
     * GET /api/admin/users/{user}
     * Get a specific user's details
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['wallet', 'ajoGroups', 'contributions' => fn($q) => $q->latest()->limit(10)]);

        return $this->successResponse(new UserResource($user));
    }

    /**
     * PATCH /api/admin/users/{user}/suspend
     * Suspend or reactivate a user
     */
    public function toggleSuspend(User $user): JsonResponse
    {
        if ($user->isSuperAdmin()) {
            return $this->forbiddenResponse('Super Admin accounts cannot be suspended.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status  = $user->is_active ? 'reactivated' : 'suspended';
        $message = "User account has been {$status} successfully.";

        return $this->successResponse(new UserResource($user->fresh()), $message);
    }

    /**
     * PATCH /api/admin/users/{user}/promote
     * Promote a user to admin (super_admin only)
     */
    public function promote(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role' => ['required', 'in:user,admin'],
        ]);

        if ($user->isSuperAdmin()) {
            return $this->forbiddenResponse('Super Admin role cannot be changed.');
        }

        $user->update(['role' => $request->role]);

        return $this->successResponse(new UserResource($user->fresh()), "User role updated to {$request->role}.");
    }

    /**
     * GET /api/admin/users/defaulters
     * List members who have defaulted on contributions
     */
    public function defaulters(): JsonResponse
    {
        $defaulters = User::whereHas('contributions', fn($q) => $q->where('status', 'defaulted'))
            ->with([
                'contributions' => fn($q) => $q->where('status', 'defaulted')->with('ajoGroup'),
                'wallet',
            ])
            ->get()
            ->map(function ($user) {
                return [
                    'user'              => new UserResource($user),
                    'defaulted_count'   => $user->contributions->count(),
                    'groups_defaulted'  => $user->contributions->pluck('ajoGroup.name')->unique()->values(),
                ];
            });

        return $this->successResponse($defaulters, 'Defaulters retrieved successfully');
    }
}
