<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AjoGroup;
use App\Models\Contribution;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/admin/analytics
     * System-wide analytics dashboard
     */
    public function index(): JsonResponse
    {
        $totalUsers        = User::where('role', 'user')->count();
        $activeUsers       = User::where('role', 'user')->where('is_active', true)->count();
        $suspendedUsers    = User::where('role', 'user')->where('is_active', false)->count();

        $totalGroups       = AjoGroup::count();
        $activeGroups      = AjoGroup::where('status', 'active')->count();
        $completedGroups   = AjoGroup::where('status', 'completed')->count();

        $totalTransacted   = Transaction::where('status', 'successful')->where('type', 'credit')->sum('amount');
        $totalFunded       = Transaction::where('status', 'successful')->where('category', 'wallet_funding')->sum('amount');
        $totalContributed  = Transaction::where('status', 'successful')->where('category', 'ajo_contribution')->sum('amount');
        $totalPayouts      = Transaction::where('status', 'successful')->where('category', 'ajo_payout')->sum('amount');

        $defaultersCount   = User::whereHas('contributions', fn($q) => $q->where('status', 'defaulted'))->count();

        // Monthly transaction trend (last 6 months)
        $monthlyTrend = Transaction::where('status', 'successful')
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $this->successResponse([
            'users' => [
                'total'     => $totalUsers,
                'active'    => $activeUsers,
                'suspended' => $suspendedUsers,
                'defaulters'=> $defaultersCount,
            ],
            'groups' => [
                'total'     => $totalGroups,
                'active'    => $activeGroups,
                'completed' => $completedGroups,
                'pending'   => $totalGroups - $activeGroups - $completedGroups,
            ],
            'financials' => [
                'total_transacted'  => number_format($totalTransacted, 2),
                'total_funded'      => number_format($totalFunded, 2),
                'total_contributed' => number_format($totalContributed, 2),
                'total_payouts'     => number_format($totalPayouts, 2),
            ],
            'monthly_trend' => $monthlyTrend,
        ], 'Analytics retrieved successfully');
    }
}
