<?php

namespace App\Http\Controllers;

use App\Models\TreasuryReward;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TreasuryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Treasury/Index', [
            'profile' => $user->profile,
            'rewards' => $user->treasuryRewards()->latest()->get(),
        ]);
    }

    public function storeReward(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cost_coin' => ['required', 'integer', 'min:0'],
        ]);

        $request->user()->treasuryRewards()->create($data);

        return redirect()->back();
    }

    public function buy(Request $request, TreasuryReward $reward)
    {
        // ownership check
        abort_unless($reward->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $profile = $request->user()->profile;

        // cek saldo
        if ($profile->coin_balance < $reward->cost_coin) {
            return redirect()->back()->withErrors([
                'coin' => 'Coin tidak cukup untuk membeli reward ini.',
            ]);
        }

        // kurangi coin
        $profile->coin_balance -= $reward->cost_coin;
        $profile->save();

        // log purchase
        $request->user()->treasuryPurchases()->create([
            'treasury_reward_id' => $reward->id,
            'cost_coin' => $reward->cost_coin,
            'purchased_at' => now(),
            'note' => $data['note'] ?? null,
        ]);

        return redirect()->back();
    }
}
