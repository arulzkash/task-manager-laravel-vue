<?php

namespace App\Http\Controllers;

use App\Models\Quest;
use App\Services\BadgeService;
use Illuminate\Http\Request;

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
            return redirect()->back()->withErrors([
                'complete' => 'Quest masih locked, tidak bisa di-complete.',
            ]);
        }

        // Kalau non-repeatable dan sudah done: stop (biar gak double reward)
        if (! $quest->is_repeatable && $quest->status === 'done') {
            return redirect()->back();
        }

        // Untuk non-repeatable: set jadi done
        if (! $quest->is_repeatable) {
            $quest->update([
                'status' => 'done',
                'completed_at' => now(),
            ]);
        }

        // Log completion (repeatable: setiap submit bikin log baru)
        $request->user()->questCompletions()->create([
            'quest_id' => $quest->id,
            'xp_awarded' => $quest->xp_reward,
            'coin_awarded' => $quest->coin_reward,
            'completed_at' => now(),
            'note' => $data['note'] ?? null,
        ]);

        // Update profile + streak (NEW SYSTEM)
        $profile = $request->user()->profile;

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $lastActive = $profile->last_active_date;

        // REALTIME STREAK (leaderboard)
        if ($lastActive === $today) {
            // sudah aktif hari ini -> streak_current tetap
        } elseif ($lastActive === $yesterday) {
            $profile->streak_current = (int) ($profile->streak_current ?? 0) + 1;
        } else {
            // aktif lagi setelah jeda (atau baru pertama) -> mulai dari 1
            $profile->streak_current = 1;
        }

        // set last active hari ini
        $profile->last_active_date = $today;

        // update best streak
        $profile->streak_best = max(
            (int) ($profile->streak_best ?? 0),
            (int) ($profile->streak_current ?? 0)
        );

        // LEGACY SYNC (biar UI lama konsisten)
        $profile->current_streak = (int) ($profile->streak_current ?? 0);
        $profile->last_quest_completed_at = $today;

        // XP + coin
        $profile->xp_total += $quest->xp_reward;
        $profile->coin_balance += $quest->coin_reward;

        $profile->save();

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
