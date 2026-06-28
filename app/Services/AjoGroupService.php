<?php

namespace App\Services;

use App\Models\AjoGroup;
use App\Models\AjoGroupMember;
use App\Models\Contribution;
use App\Models\ContributionCycle;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AjoGroupService
{
    public function __construct(private WalletService $walletService) {}

    /**
     * Create a new Ajo group (creator auto-joins as slot 1, group admin)
     */
    public function createGroup(User $user, array $data): AjoGroup
    {
        return DB::transaction(function () use ($user, $data) {
            $group = AjoGroup::create([
                'name'                => $data['name'],
                'code'                => AjoGroup::generateCode(),
                'description'         => $data['description'] ?? null,
                'created_by'          => $user->id,
                'contribution_amount' => $data['contribution_amount'],
                'frequency'           => $data['frequency'],
                'max_members'         => $data['max_members'],
                'status'              => 'pending',
            ]);

            // Creator joins as slot 1 and becomes group admin
            AjoGroupMember::create([
                'ajo_group_id' => $group->id,
                'user_id'      => $user->id,
                'slot_number'  => 1,
                'is_admin'     => true,
                'status'       => 'active',
            ]);

            return $group->load(['creator', 'groupMembers.user']);
        });
    }

    /**
     * Join a group using its code
     */
    public function joinGroup(User $user, string $code): AjoGroup
    {
        $group = AjoGroup::where('code', $code)->first();

        if (!$group) {
            throw new \Exception('Group not found. Please check the code and try again.');
        }

        if ($group->status !== 'pending') {
            throw new \Exception('This group is no longer accepting new members.');
        }

        if ($group->isFull()) {
            throw new \Exception('This savings group is already full.');
        }

        $alreadyMember = AjoGroupMember::where('ajo_group_id', $group->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyMember) {
            throw new \Exception('You are already a member of this group.');
        }

        $nextSlot = $group->groupMembers()->max('slot_number') + 1;

        AjoGroupMember::create([
            'ajo_group_id' => $group->id,
            'user_id'      => $user->id,
            'slot_number'  => $nextSlot,
            'is_admin'     => false,
            'status'       => 'active',
        ]);

        return $group->load(['creator', 'groupMembers.user']);
    }

    /**
     * Start the group — admin activates it and creates cycle 1
     */
    public function startGroup(User $user, AjoGroup $group): AjoGroup
    {
        $membership = AjoGroupMember::where('ajo_group_id', $group->id)
            ->where('user_id', $user->id)
            ->where('is_admin', true)
            ->first();

        if (!$membership) {
            throw new \Exception('Only the group admin can start the savings cycle.');
        }

        if ($group->status !== 'pending') {
            throw new \Exception('This group has already been started.');
        }

        if ($group->member_count < 2) {
            throw new \Exception('A group needs at least 2 members before it can start.');
        }

        return DB::transaction(function () use ($group) {
            $group->update([
                'status'     => 'active',
                'start_date' => now()->toDateString(),
            ]);

            // Create cycle 1
            $this->createNextCycle($group);

            return $group->fresh(['creator', 'groupMembers.user', 'contributionCycles']);
        });
    }

    /**
     * Make a contribution for the active cycle
     */
    public function makeContribution(User $user, AjoGroup $group): Contribution
    {
        $membership = AjoGroupMember::where('ajo_group_id', $group->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$membership) {
            throw new \Exception('You are not an active member of this group.');
        }

        if ($group->status !== 'active') {
            throw new \Exception('This group is not currently active.');
        }

        $cycle = $group->currentCycle();

        if (!$cycle) {
            throw new \Exception('No active contribution cycle found.');
        }

        $existingContribution = Contribution::where('contribution_cycle_id', $cycle->id)
            ->where('user_id', $user->id)
            ->where('status', 'paid')
            ->first();

        if ($existingContribution) {
            throw new \Exception('You have already contributed for this cycle.');
        }

        return DB::transaction(function () use ($user, $group, $cycle) {
            // Debit user wallet
            $transaction = $this->walletService->debitWallet(
                user: $user,
                amount: $group->contribution_amount,
                category: 'ajo_contribution',
                description: "Contribution for {$group->name} - Cycle {$cycle->cycle_number}",
                metadata: [
                    'group_id'  => $group->id,
                    'cycle_id'  => $cycle->id,
                    'recipient' => $cycle->recipient->full_name,
                ]
            );

            // Record contribution
            $contribution = Contribution::updateOrCreate(
                [
                    'contribution_cycle_id' => $cycle->id,
                    'user_id'               => $user->id,
                ],
                [
                    'ajo_group_id'   => $group->id,
                    'transaction_id' => $transaction->id,
                    'amount'         => $group->contribution_amount,
                    'status'         => 'paid',
                    'paid_at'        => now(),
                ]
            );

            // Update cycle collected amount
            $cycle->increment('amount_collected', $group->contribution_amount);

            // Check if cycle is fully funded — auto payout
            if ($cycle->fresh()->isFullyFunded()) {
                $this->processPayout($cycle->fresh());
            }

            return $contribution->load(['user', 'contributionCycle']);
        });
    }

    /**
     * Get the collection board for a group
     */
    public function getCollectionBoard(AjoGroup $group): array
    {
        $currentCycle = $group->currentCycle();

        $members = $group->groupMembers()
            ->with('user')
            ->orderBy('slot_number')
            ->get()
            ->map(function ($member) use ($currentCycle) {
                $contributionStatus = null;

                if ($currentCycle) {
                    $contribution = Contribution::where('contribution_cycle_id', $currentCycle->id)
                        ->where('user_id', $member->user_id)
                        ->first();
                    $contributionStatus = $contribution?->status ?? 'pending';
                }

                return [
                    'slot_number'         => $member->slot_number,
                    'user'                => [
                        'id'             => $member->user->id,
                        'full_name'      => $member->user->full_name,
                        'account_number' => $member->user->account_number,
                    ],
                    'has_collected'       => $member->has_collected,
                    'collected_at'        => $member->collected_at,
                    'contribution_status' => $contributionStatus, // paid/pending/defaulted
                    'is_next_recipient'   => $currentCycle && $currentCycle->recipient_id === $member->user_id && !$member->has_collected,
                ];
            });

        return [
            'group'          => [
                'id'                  => $group->id,
                'name'                => $group->name,
                'status'              => $group->status,
                'contribution_amount' => number_format($group->contribution_amount, 2),
                'frequency'           => $group->frequency,
            ],
            'current_cycle'  => $currentCycle ? [
                'cycle_number'     => $currentCycle->cycle_number,
                'recipient'        => $currentCycle->recipient->full_name,
                'expected_total'   => number_format($currentCycle->expected_total, 2),
                'amount_collected' => number_format($currentCycle->amount_collected, 2),
                'due_date'         => $currentCycle->due_date->toDateString(),
                'status'           => $currentCycle->status,
            ] : null,
            'members'        => $members,
            'collected'      => $members->where('has_collected', true)->values(),
            'pending'        => $members->where('has_collected', false)->values(),
        ];
    }

    /**
     * Process payout to cycle recipient
     */
    private function processPayout(ContributionCycle $cycle): void
    {
        $recipient = $cycle->recipient;
        $group     = $cycle->ajoGroup;

        // Credit the recipient's wallet
        $this->walletService->creditWallet(
            user: $recipient,
            amount: $cycle->amount_collected,
            category: 'ajo_payout',
            description: "Ajo payout from {$group->name} - Cycle {$cycle->cycle_number}",
            metadata: [
                'group_id' => $group->id,
                'cycle_id' => $cycle->id,
            ]
        );

        // Mark cycle as completed
        $cycle->update([
            'status'      => 'completed',
            'paid_out_at' => now(),
        ]);

        // Mark member as collected
        AjoGroupMember::where('ajo_group_id', $group->id)
            ->where('user_id', $recipient->id)
            ->update([
                'has_collected' => true,
                'collected_at'  => now(),
            ]);

        // Check if all members have collected — group is done
        $allCollected = AjoGroupMember::where('ajo_group_id', $group->id)
            ->where('has_collected', false)
            ->doesntExist();

        if ($allCollected) {
            $group->update(['status' => 'completed', 'end_date' => now()->toDateString()]);
        } else {
            // Create next cycle
            $this->createNextCycle($group);
        }
    }

    /**
     * Create next contribution cycle — next slot member is recipient
     */
    private function createNextCycle(AjoGroup $group): ContributionCycle
    {
        $lastCycle  = $group->contributionCycles()->latest('cycle_number')->first();
        $cycleNumber = $lastCycle ? $lastCycle->cycle_number + 1 : 1;

        // Find the next member who hasn't collected yet
        $nextMember = AjoGroupMember::where('ajo_group_id', $group->id)
            ->where('has_collected', false)
            ->where('status', 'active')
            ->orderBy('slot_number')
            ->first();

        $dueDate = $this->calculateDueDate($group->frequency);

        return ContributionCycle::create([
            'ajo_group_id'     => $group->id,
            'recipient_id'     => $nextMember->user_id,
            'cycle_number'     => $cycleNumber,
            'expected_total'   => $group->contribution_amount * $group->member_count,
            'amount_collected' => 0,
            'status'           => 'active',
            'due_date'         => $dueDate,
        ]);
    }

    private function calculateDueDate(string $frequency): string
    {
        return match ($frequency) {
            'daily'    => now()->addDay()->toDateString(),
            'weekly'   => now()->addWeek()->toDateString(),
            'biweekly' => now()->addWeeks(2)->toDateString(),
            'monthly'  => now()->addMonth()->toDateString(),
        };
    }
}
