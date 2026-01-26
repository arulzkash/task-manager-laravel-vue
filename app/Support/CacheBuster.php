<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class CacheBuster
{
    public static function onQuestComplete(int $userId): void
    {
        $dateKey = CacheKeys::todayJakarta();

        // global leaderboard cache (per-hari)
        Cache::forget(CacheKeys::leaderboardRoster($dateKey));
        Cache::forget(CacheKeys::leaderboardBadges($dateKey));

        // dashboard badge snippet
        Cache::forget(CacheKeys::dashboardTopBadge($userId));

        // navbar profile (coin/xp berubah)
        Cache::forget(CacheKeys::navProfile($userId));
    }
}
