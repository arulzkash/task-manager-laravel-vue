<?php

namespace App\Http\Controllers;

use App\Models\Quest;
use App\Services\BadgeService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class QuestController extends Controller
{
    //
    public function store(Request $request)
    {
        // Validate and store the quest
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:todo,in_progress,locked'],
            'type' => ['required', 'string', 'max:100'],
            'xp_reward' => ['required', 'integer', 'min:0'],
            'coin_reward' => ['required', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'is_repeatable' => ['required', 'boolean'],
        ]);

        $data['is_repeatable'] = $request->boolean('is_repeatable');

        // RULE: repeatable = unlimited submit => due_date harus null
        if ($data['is_repeatable']) {
            $data['due_date'] = null;
        }

        $request->user()->quests()->create($data);

        return redirect()->back();
    }

    public function complete(Request $request, Quest $quest)
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->authorize('update', $quest);

        if ($quest->status === 'locked') {
            return redirect()->back()->withErrors(['complete' => 'Quest is locked.']);
        }

        // Prevent double counting for non-repeatables
        if (! $quest->is_repeatable && $quest->status === 'done') {
            return redirect()->back();
        }

        // Mark as done locally
        if (! $quest->is_repeatable) {
            $quest->update(['status' => 'done', 'completed_at' => now()]);
        }

        // Log completion
        $request->user()->questCompletions()->create([
            'quest_id' => $quest->id,
            'xp_awarded' => $quest->xp_reward,
            'coin_awarded' => $quest->coin_reward,
            'completed_at' => now(),
            'note' => $data['note'] ?? null,
        ]);

        // --- CORE LOGIC: STREAK & FREEZE SYSTEM (LAZY UPDATE) ---
        $profile = $request->user()->profile;
        
        // PENTING: Gunakan startOfDay untuk komparasi tanggal murni
        $today = now()->startOfDay(); 
        
        // 1. Weekly Freeze Reset Logic
        // Cek apakah minggu sudah berganti sejak terakhir kali freeze dipakai?
        // (Default start of week: Senin)
        $currentWeekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        
        if ($profile->freezes_used_week_start !== $currentWeekStart) {
            // Reset jatah freeze mingguan karena sudah masuk minggu baru
            $profile->freezes_used_week_start = $currentWeekStart;
            $profile->freezes_used_count = 0;
        }

        // 2. Streak Calculation
        if ($profile->last_active_date) {
            $lastActive = Carbon::parse($profile->last_active_date)->startOfDay();
            $diffInDays = $lastActive->diffInDays($today); // Selisih hari absolut

            if ($diffInDays == 0) {
                // Kasus A: Masih hari yang sama.
                // Tidak ada perubahan streak.
            } elseif ($diffInDays == 1) {
                // Kasus B: Login besoknya (Perfect Streak).
                $profile->streak_current++;
            } else {
                // Kasus C: Ada Gap (Bolong).
                // diff 2 hari = bolong 1 hari (kemarin).
                // diff 3 hari = bolong 2 hari.
                $daysMissed = $diffInDays - 1; 
                
                // Cek sisa freeze minggu ini
                // Limit misal: 2 freeze per minggu
                $freezesLeft = max(0, 2 - $profile->freezes_used_count);

                if ($daysMissed <= $freezesLeft) {
                    // --> SELAMAT! Streak lanjut pakai Freeze.
                    $profile->streak_current++; 
                    $profile->freezes_used_count += $daysMissed;
                    $profile->freezes_used_total += $daysMissed;
                    // Catat bahwa streak ini "diselamatkan" sampai hari ini
                    $profile->streak_maintained_through = $today->toDateString();
                } else {
                    // --> GAME OVER. Reset Streak.
                    $profile->streak_current = 1;
                    $profile->streak_resets_total++;
                }
            }
        } else {
            // Kasus D: User baru pertama kali
            $profile->streak_current = 1;
        }

        // 3. Save State
        $profile->last_active_date = $today->toDateString();
        
        // Update Best Streak Record
        if ($profile->streak_current > $profile->streak_best) {
            $profile->streak_best = $profile->streak_current;
        }

        // Legacy Sync (Opsional, buat jaga kompatibilitas UI lama)
        $profile->current_streak = $profile->streak_current;
        $profile->last_quest_completed_at = $today->toDateString();

        // Update Economy
        $profile->xp_total += $quest->xp_reward;
        $profile->coin_balance += $quest->coin_reward;

        $profile->save();

        // 4. Badge Sync
        // (Pastikan BadgeService sudah ada logic cek streak_best)
        app(BadgeService::class)->syncForUser($request->user());

        return redirect()->back();
    }

    public function update(Request $request, Quest $quest)
    {
        $this->authorize('update', $quest);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:todo,in_progress,locked'], // done gak boleh dari edit
            'type' => ['required', 'string', 'max:100'],
            'xp_reward' => ['required', 'integer', 'min:0'],
            'coin_reward' => ['required', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'is_repeatable' => ['required', 'boolean'],
        ]);

        $data['is_repeatable'] = $request->boolean('is_repeatable');
        $data['due_date'] = $request->input('due_date') ?: null;

        $quest->update($data);

        return redirect()->back();
    }

    public function destroy(Request $request, Quest $quest)
    {
        $this->authorize('delete', $quest);

        // block delete kalau ada completion logs
        if ($quest->completions()->exists()) {
            return redirect()->back()->withErrors([
                'delete' => 'Quest sudah punya completion log, jadi tidak bisa dihapus.',
            ]);
        }

        $quest->delete();

        return redirect()->back();
    }
}
