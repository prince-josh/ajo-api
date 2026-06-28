<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AjoGroup;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/admin/groups
     * List all Ajo groups with filters
     */
    public function index(Request $request): JsonResponse
    {
        $groups = AjoGroup::with(['creator', 'groupMembers'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', $request->search);
            })
            ->withCount('groupMembers')
            ->latest()
            ->paginate($request->get('per_page', 20));

        return $this->paginatedResponse($groups, 'Groups retrieved successfully');
    }

    /**
     * GET /api/admin/groups/{group}
     * Get a single group's full details
     */
    public function show(AjoGroup $group): JsonResponse
    {
        $group->load([
            'creator',
            'groupMembers.user',
            'contributionCycles.recipient',
            'contributionCycles.contributions.user',
        ]);

        return $this->successResponse($group);
    }

    /**
     * PATCH /api/admin/groups/{group}/suspend
     * Suspend or reactivate a group
     */
    public function toggleSuspend(AjoGroup $group): JsonResponse
    {
        if ($group->status === 'completed') {
            return $this->errorResponse('Completed groups cannot be modified.');
        }

        $newStatus = $group->status === 'suspended' ? 'active' : 'suspended';
        $group->update(['status' => $newStatus]);

        return $this->successResponse($group, "Group has been {$newStatus}.");
    }

    /**
     * DELETE /api/admin/groups/{group}
     * Soft-delete a pending group
     */
    public function destroy(AjoGroup $group): JsonResponse
    {
        if ($group->status !== 'pending') {
            return $this->errorResponse('Only pending groups can be deleted. Suspend active groups instead.');
        }

        $group->delete();

        return $this->successResponse(null, 'Group has been deleted.');
    }
}
