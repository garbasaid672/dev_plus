<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function create(WithdrawalService $withdrawalService): View
    {
        return view('withdrawals.create', [
            'balance' => $withdrawalService->getAvailableBalance(),
            'methods' => WithdrawalService::METHODS,
        ]);
    }

    public function store(Request $request, WithdrawalService $withdrawalService): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1000'],
            'method' => ['required', 'string', 'in:'.implode(',', array_keys(WithdrawalService::METHODS))],
            'account_details' => ['required', 'string', 'max:255'],
        ]);

        if (! $user instanceof User) {
            abort(401);
        }

        $withdrawalService->create(
            $user,
            (int) $data['amount'],
            $data['method'],
            $data['account_details'],
        );

        return back()->with('success', 'Votre demande de retrait a été envoyée.');
    }
}
