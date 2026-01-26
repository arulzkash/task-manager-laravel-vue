<?php

namespace App\Support;

use Carbon\Carbon;

class CacheKeys
{
    public static function todayJakarta(): string
    {
        return Carbon::now('Asia/Jakarta')->toDateString(); // YYYY-MM-DD
    }

    public static function leaderboardRoster(string $dateJakarta): string
    {
        return "leaderboard:global_roster:{$dateJakarta}";
    }

    public static function leaderboardBadges(string $dateJakarta): string
    {
        return "leaderboard:global_badges:{$dateJakarta}";
    }

    public static function dashboardTopBadge(int $userId): string
    {
        return "dashboard:top_badge:{$userId}";
    }

    public static function navProfile(int $userId): string
    {
        return "nav_profile:{$userId}";
    }
}
