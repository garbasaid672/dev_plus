<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawalService
{
    public const METHODS = [
        'MTN' => 'MTN MoMo',
        'Moov' => 'Moov Money',
        'Celtiis' => 'Celtiis Cash',
    ];

    public function getAvailableBalance(): int
    {
        $confirmed = (float) Order::query()
            ->where('status', 'completed')
            ->sum('total_amount');

        $reserved = (float) Withdrawal::query()
            ->whereIn('status', ['pending', 'approved', 'paid'])
            ->sum('amount');

        return max(0, (int) ($confirmed - $reserved));
    }

    public function create(User $user, int $amount, string $method, string $accountDetails): Withdrawal
    {
        return DB::transaction(function () use ($user, $amount, $method, $accountDetails): Withdrawal {
            Order::query()
                ->where('status', 'completed')
                ->orderBy('id')
                ->lockForUpdate()
                ->first(['id']);

            if ($user->withdrawals()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'amount' => 'Vous avez déjà une demande en attente.',
                ]);
            }

            if ($amount > $this->getAvailableBalance()) {
                throw ValidationException::withMessages([
                    'amount' => 'Solde insuffisant.',
                ]);
            }

            return $user->withdrawals()->create([
                'amount' => $amount,
                'method' => $method,
                'account_details' => $accountDetails,
            ]);
        });
    }
}
