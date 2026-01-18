<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Profile;

class StreakBackfill extends Command
{
    protected $signature = 'streak:backfill';
    protected $description = 'Backfill streak fields (new system) from legacy columns';

    public function handle(): int
    {
        $updated = 0;

        Profile::query()->chunkById(200, function ($profiles) use (&$updated) {
            foreach ($profiles as $p) {
                $legacyStreak = (int) ($p->current_streak ?? 0);
                $legacyLast   = $p->last_quest_completed_at;

                $p->streak_current = $p->streak_current ?: $legacyStreak;
                $p->streak_best = max((int)($p->streak_best ?? 0), $legacyStreak);

                if (!$p->last_active_date && $legacyLast) {
                    $p->last_active_date = $legacyLast;
                }

                $p->save();
                $updated++;
            }
        });

        $this->info("Backfill done. Profiles updated: {$updated}");
        return self::SUCCESS;
    }
}
