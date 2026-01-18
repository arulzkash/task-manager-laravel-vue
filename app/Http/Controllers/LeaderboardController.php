<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        // limit default 50, max 100 biar aman
        $limit = (int) $request->query('limit', 50);
        $limit = max(1, min($limit, 100));

        // ORDER sesuai konsep:
        // streak_current DESC
        // streak_best DESC
        // last_active_date DESC
        // users.id ASC (stabil)
        //
        // COALESCE biar null dianggap 0 (streak kosong jadi paling bawah)
        $orderSql = implode(', ', [
            'COALESCE(profiles.streak_current, 0) DESC',
            'COALESCE(profiles.streak_best, 0) DESC',
            'profiles.last_active_date DESC',
            'users.id ASC',
        ]);

        // Subquery: bikin "rank" pakai ROW_NUMBER() (lebih rapi & cepat)
        $sub = DB::table('profiles')
            ->join('users', 'users.id', '=', 'profiles.user_id')
            ->select([
                'profiles.user_id',
                'users.name',
                'profiles.streak_current',
                'profiles.streak_best',
                'profiles.last_active_date',
            ])
            ->selectRaw("ROW_NUMBER() OVER (ORDER BY {$orderSql}) as rank");

        $ranked = DB::query()->fromSub($sub, 'ranked');

        // Top N
        $rows = $ranked
            ->orderBy('rank')
            ->limit($limit)
            ->get();

        // Me (rank global)
        $meRow = $ranked
            ->where('user_id', $userId)
            ->first();

        $items = $rows->map(fn($r) => [
            'rank' => (int) $r->rank,
            'user' => [
                'id' => (int) $r->user_id,
                'name' => $r->name,
            ],
            'streak_current' => (int) ($r->streak_current ?? 0),
            'streak_best' => (int) ($r->streak_best ?? 0),
            'last_active_date' => $r->last_active_date,
        ])->values();

        $me = $meRow ? [
            'rank' => (int) $meRow->rank,
            'user' => [
                'id' => (int) $meRow->user_id,
                'name' => $meRow->name,
            ],
            'streak_current' => (int) ($meRow->streak_current ?? 0),
            'streak_best' => (int) ($meRow->streak_best ?? 0),
            'last_active_date' => $meRow->last_active_date,
        ] : null;

        return response()->json([
            'items' => $items,
            'me' => $me,
        ]);
    }
}
