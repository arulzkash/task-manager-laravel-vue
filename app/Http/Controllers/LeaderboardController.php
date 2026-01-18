<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    public function page(Request $request)
    {
        $data = $this->buildLeaderboardData($request);

        return Inertia::render('Leaderboard/Index', [
            'items' => $data['items'],
            'me' => $data['me'],
        ]);
    }

    public function index(Request $request)
    {
        return response()->json($this->buildLeaderboardData($request));
    }

    private function buildLeaderboardData(Request $request): array
    {
        $userId = $request->user()->id;

        $limit = (int) $request->query('limit', 50);
        $limit = max(1, min($limit, 100));

        // Use one "now" to avoid drift
        $now = now();

        $today = $now->toDateString();
        $yesterday = $now->copy()->subDay()->toDateString();

        $start7 = $now->copy()->subDays(6)->startOfDay();
        $end7 = $now->copy()->endOfDay();

        // active_days_last_7d: count distinct dates with >=1 completion
        $active7 = DB::table('quest_completions')
            ->select([
                'user_id',
                DB::raw('COUNT(DISTINCT DATE(completed_at)) as active_days_last_7d'),
            ])
            ->whereBetween('completed_at', [$start7, $end7])
            ->groupBy('user_id');

        // last_active_at: max completion timestamp (to seconds)
        $lastActiveAt = DB::table('quest_completions')
            ->select([
                'user_id',
                DB::raw('MAX(completed_at) as last_active_at'),
            ])
            ->groupBy('user_id');

        // NULL-safe ordering for last_active_at:
        // (NULL goes to bottom) then newest first
        $orderSql = implode(', ', [
            'COALESCE(profiles.streak_current, 0) DESC',
            'COALESCE(profiles.streak_best, 0) DESC',
            'COALESCE(active7.active_days_last_7d, 0) DESC',
            'CASE WHEN last_active_at.last_active_at IS NULL THEN 1 ELSE 0 END ASC',
            'last_active_at.last_active_at DESC',
            'users.id ASC',
        ]);

        $sub = DB::table('profiles')
            ->join('users', 'users.id', '=', 'profiles.user_id')
            ->leftJoinSub($active7, 'active7', function ($join) {
                $join->on('active7.user_id', '=', 'profiles.user_id');
            })
            ->leftJoinSub($lastActiveAt, 'last_active_at', function ($join) {
                $join->on('last_active_at.user_id', '=', 'profiles.user_id');
            })
            ->select([
                'profiles.user_id',
                'users.name',
                'profiles.streak_current',
                'profiles.streak_best',
            ])
            ->selectRaw('COALESCE(active7.active_days_last_7d, 0) as active_days_last_7d')
            ->selectRaw('last_active_at.last_active_at as last_active_at')
            ->selectRaw(
                "CASE
                    WHEN DATE(last_active_at.last_active_at) = ? THEN 'On Fire'
                    WHEN DATE(last_active_at.last_active_at) = ? THEN 'Pending'
                    ELSE 'Cold'
                 END as status",
                [$today, $yesterday]
            )
            ->selectRaw("ROW_NUMBER() OVER (ORDER BY {$orderSql}) as rank");

        $ranked = DB::query()->fromSub($sub, 'ranked');

        // ✅ don't overwrite this var later
        $topRows = $ranked->orderBy('rank')->limit($limit)->get();
        $meRow = DB::query()->fromSub($sub, 'ranked')->where('user_id', $userId)->first();

        // --- BADGES: load only visible users + me ---
        $userIds = $topRows->pluck('user_id')->map(fn ($v) => (int) $v)->all();
        if ($meRow) $userIds[] = (int) $meRow->user_id;
        $userIds = array_values(array_unique($userIds));

        $badgeTopByUser = [];

        if (count($userIds) > 0) {
            $badgeRows = DB::table('user_badges')
                ->join('badges', 'badges.id', '=', 'user_badges.badge_id')
                ->whereIn('user_badges.user_id', $userIds)
                ->get([
                    'user_badges.user_id',
                    'user_badges.earned_at',
                    'badges.id as badge_id',
                    'badges.key as badge_key',
                    'badges.name as badge_name',
                    'badges.category as badge_category',
                ]);

            $priority = [
                'streak_100' => 100,
                'streak_60' => 60,
                'streak_30' => 30,
                'streak_14' => 14,
                'streak_7' => 7,
                'streak_3' => 3,
                'comeback_kid' => 2,
                'second_wind' => 1,
            ];

            $grouped = $badgeRows->groupBy('user_id');

            foreach ($grouped as $uid => $list) {
                $best = $list->sort(function ($a, $b) use ($priority) {
                    $pa = $priority[$a->badge_key] ?? 0;
                    $pb = $priority[$b->badge_key] ?? 0;

                    if ($pa !== $pb) return $pb <=> $pa;

                    return strtotime($b->earned_at) <=> strtotime($a->earned_at);
                })->first();

                if ($best) {
                    $badgeTopByUser[(int) $uid] = [
                        'id' => (int) $best->badge_id,
                        'key' => (string) $best->badge_key,
                        'name' => (string) $best->badge_name,
                        'category' => (string) $best->badge_category,
                    ];
                }
            }
        }

        $mapRow = function ($r) use ($badgeTopByUser) {
            $uid = (int) $r->user_id;

            return [
                'rank' => (int) $r->rank,
                'user' => [
                    'id' => $uid,
                    'name' => $r->name,
                ],
                'status' => $r->status,
                'streak_current' => (int) ($r->streak_current ?? 0),
                'streak_best' => (int) ($r->streak_best ?? 0),
                'active_days_last_7d' => (int) ($r->active_days_last_7d ?? 0),
                'last_active_at' => $r->last_active_at, // datetime string (seconds)
                'badge_top' => $badgeTopByUser[$uid] ?? null,
            ];
        };

        return [
            'items' => $topRows->map($mapRow)->values(),
            'me' => $meRow ? $mapRow($meRow) : null,
        ];
    }
}
