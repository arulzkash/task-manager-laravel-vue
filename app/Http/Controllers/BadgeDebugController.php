<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class BadgeDebugController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load(['profile', 'badges']);

        return Inertia::render('Debug/Badges', [
            'me' => [
                'id' => $user->id,
                'name' => $user->name,
                'streak_current' => (int) ($user->profile->streak_current ?? 0),
                'streak_best' => (int) ($user->profile->streak_best ?? 0),
                'freezes_used_total' => (int) ($user->profile->freezes_used_total ?? 0),
                'streak_resets_total' => (int) ($user->profile->streak_resets_total ?? 0),
                'badges' => $user->badges->map(fn($b) => [
                    'key' => $b->key,
                    'name' => $b->name,
                    'category' => $b->category,
                    'earned_at' => $b->pivot->earned_at,
                ])->values(),
            ],
        ]);
    }
}
