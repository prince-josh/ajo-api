<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AjoGroup;
use App\Services\AjoGroupService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AjoGroupController extends Controller
{
    use ApiResponse;

    public function __construct(private AjoGroupService $ajoGroupService) {}

    /**
     * GET /api/groups
     * List all groups the authenticated user belongs to
     */
    public function index(Request $request): JsonResponse
    {
        $groups = $request->user()
            ->ajoGroups()
            ->with(['creator', 'groupMembers'])
            ->latest()
            ->paginate(10);

        return $this->paginatedResponse($groups, 'Groups retrieved successfully');
    }

    /**
     * POST /api/groups
     * Create a new Ajo group
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'                => ['required', 'string', 'max:150'],
            'description'         => ['nullable', 'string'],
            'contribution_amount' => ['required', 'numeric', 'min:500'],
            'frequency'           => ['required', 'in:daily,weekly,biweekly,monthly'],
            'max_members'         => ['required', 'integer', 'min:2', 'max:100'],
        ]);

        $group = $this->ajoGroupService->createGroup($request->user(), $request->validated());

        return $this->createdResponse($group, 'Savings group created successfully');
    }

    /**
     * GET /api/groups/{group}
     * Get a single group's details
     */
    public function show(AjoGroup $group): JsonResponse
    {
        $group->load(['creator', 'groupMembers.user', 'contributionCycles.recipient']);

        return $this->successResponse($group);
    }

    /**
     * POST /api/groups/join
     * Join a group using its code
     */
    public function join(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:8'],
        ]);

        $group = $this->ajoGroupService->joinGroup($request->user(), strtoupper($request->code));

        return $this->successResponse($group, "You've successfully joined the savings group.");
    }

    /**
     * POST /api/groups/{group}/start
     * Start the group (admin only)
     */
    public function start(Request $request, AjoGroup $group): JsonResponse
    {
        $group = $this->ajoGroupService->startGroup($request->user(), $group);

        return $this->successResponse($group, 'Savings group has started! Cycle 1 is now active.');
    }

    /**
     * GET /api/groups/{group}/board
     * Get the Ajo collection board
     */
    public function board(AjoGroup $group): JsonResponse
    {
        $board = $this->ajoGroupService->getCollectionBoard($group);

        return $this->successResponse($board, 'Collection board retrieved successfully');
    }

    /**
     * POST /api/groups/{group}/contribute
     * Make a contribution for the active cycle
     */
    public function contribute(Request $request, AjoGroup $group): JsonResponse
    {
        $contribution = $this->ajoGroupService->makeContribution($request->user(), $group);

        return $this->successResponse($contribution, "Contribution of ₦" . number_format($group->contribution_amount, 2) . " made successfully.");
    }

    /**
     * GET /api/groups/{group}/contributions
     * Get contribution history for a group
     */
    public function contributions(Request $request, AjoGroup $group): JsonResponse
    {
        $contributions = $group->contributions()
            ->where('user_id', $request->user()->id)
            ->with(['contributionCycle'])
            ->latest()
            ->paginate(15);

        return $this->paginatedResponse($contributions, 'Contribution history retrieved');
    }
}
