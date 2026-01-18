<?php

namespace App\Console\Commands;

use App\Models\Profile;
use App\Services\BadgeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class StreakDaily extends Command
{
    protected $signature = 'streak:daily {--date= : Force date YYYY-MM-DD (debug)}';

    protected $description = 'Apply daily freeze/reset logic for quest streaks';

    public function handle(): int
    {
        // App timezone should be Asia/Jakarta in config/app.php
        $dateOpt = $this->option('date');

        try {
            $today = $dateOpt
                ? Carbon::createFromFormat('Y-m-d', $dateOpt)->startOfDay()
                : now()->startOfDay();
        } catch (\Throwable $e) {
            $this->error("Invalid --date. Use YYYY-MM-DD");
            return self::FAILURE;
        }

        $yesterday = $today->copy()->subDay();

        // Monday 00:00 week start (Asia/Jakarta)
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();

        $this->info("Running streak:daily for date={$today->toDateString()} (weekStart={$weekStart->toDateString()})");

        $updated = 0;

        Profile::query()->chunkById(200, function ($profiles) use ($today, $yesterday, $weekStart, &$updated) {
            foreach ($profiles as $profile) {
                $dirty = false;

                // Normalize week freeze window
                $currentWeekStart = $profile->freezes_used_week_start
                    ? Carbon::parse($profile->freezes_used_week_start)->startOfDay()
                    : null;

                if (!$currentWeekStart || !$currentWeekStart->equalTo($weekStart)) {
                    $profile->freezes_used_week_start = $weekStart->toDateString();
                    $profile->freezes_used_count = 0;
                    $dirty = true;
                }

                // Determine last active date
                if (!$profile->last_active_date) {
                    // Belum pernah aktif => gak perlu apa2
                    if ($dirty) {
                        $profile->save();
                        $updated++;
                    }
                    continue;
                }

                $lastActive = Carbon::parse($profile->last_active_date)->startOfDay();

                // If already active today or yesterday => streak safe
                if ($lastActive->equalTo($today) || $lastActive->equalTo($yesterday)) {
                    if ($dirty) {
                        $profile->save();
                        $updated++;
                    }
                    continue;
                }

                // Days missed between lastActive and today (excluding endpoints)
                // Example:
                // lastActive=Jan10, today=Jan12 => missed=1 (Jan11)
                $gapDays = $lastActive->diffInDays($today);
                $daysMissed = max(0, $gapDays - 1);

                if ($daysMissed <= 0) {
                    if ($dirty) {
                        $profile->save();
                        $updated++;
                    }
                    continue;
                }

                $freezesUsed = (int) ($profile->freezes_used_count ?? 0);
                $freezesLeft = max(0, 2 - $freezesUsed);

                if ($daysMissed <= $freezesLeft) {
                    // Consume freezes automatically
                    $profile->freezes_used_count = $freezesUsed + $daysMissed;
                    $profile->freezes_used_total = (int) ($profile->freezes_used_total ?? 0) + $daysMissed;

                    // Mark "maintained thru" as today (meaning: streak still protected up to this day)
                    $profile->streak_maintained_through = $today->toDateString();

                    $dirty = true;
                } else {
                    // Not enough freezes => reset streak (SYNC legacy too)
                    $profile->streak_current = 0;
                    $profile->current_streak = 0; // <-- TAMBAH INI biar data lama ikut reset

                    $profile->streak_resets_total = (int) ($profile->streak_resets_total ?? 0) + 1;
                    $profile->streak_maintained_through = $lastActive->toDateString();

                    $dirty = true;
                }

                if ($dirty) {
                    $profile->save();
                    app(BadgeService::class)->syncForUser($profile->user);
                    $updated++;
                }
            }
        });

        $this->info("Done. Profiles updated: {$updated}");
        return self::SUCCESS;
    }
}
