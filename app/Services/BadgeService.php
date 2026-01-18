<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\User;

class BadgeService
{
    public function syncForUser(User $user): void
    {
        $profile = $user->profile;
        if (!$profile) return;

        $streakBest = (int) ($profile->streak_best ?? 0);
        $freezesTotal = (int) ($profile->freezes_used_total ?? 0);
        $resetsTotal  = (int) ($profile->streak_resets_total ?? 0);

        $keys = [];

        // Milestones pakai streak_best
        if ($streakBest >= 3)   $keys[] = 'streak_3';
        if ($streakBest >= 7)   $keys[] = 'streak_7';
        if ($streakBest >= 14)  $keys[] = 'streak_14';
        if ($streakBest >= 30)  $keys[] = 'streak_30';
        if ($streakBest >= 60)  $keys[] = 'streak_60';
        if ($streakBest >= 100) $keys[] = 'streak_100';

        // Recovery
        if ($freezesTotal > 0) $keys[] = 'second_wind';
        if ($resetsTotal > 0 && $streakBest >= 7) $keys[] = 'comeback_kid';

        if (!$keys) return;

        $badges = Badge::whereIn('key', $keys)->get()->keyBy('key');

        foreach ($keys as $key) {
            $badge = $badges[$key] ?? null;
            if (!$badge) continue;

            if (!$user->badges()->where('badge_id', $badge->id)->exists()) {
                $user->badges()->attach($badge->id, [
                    'earned_at' => now()->toDateString(),
                ]);
            }
        }
    }
}
