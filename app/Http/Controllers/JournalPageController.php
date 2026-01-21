<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Database\UniqueConstraintViolationException;

class JournalPageController extends Controller
{
    private function todayKey(): string
    {
        return Carbon::now('Asia/Jakarta')->toDateString(); // YYYY-MM-DD
    }

    private function entryPayload(?JournalEntry $entry): ?array
    {
        if (!$entry) return null;

        return [
            'id' => $entry->id,
            'date' => $entry->date?->toDateString(),
            'body' => $entry->body,
            'sections' => $entry->sections ?? [],
            'xp_awarded' => (int)($entry->xp_awarded ?? 0),
            'coin_awarded' => (int)($entry->coin_awarded ?? 0),
            'rewarded_at' => $entry->rewarded_at ? $entry->rewarded_at->toISOString() : null,
        ];
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $todayKey = $this->todayKey();
        $date = $request->query('date', $todayKey);

        // validasi ringan untuk query param date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = $todayKey;
        }

        $entry = JournalEntry::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        return Inertia::render('Journal/Index', [
            'date' => $date,
            'todayKey' => $todayKey,
            'entry' => $this->entryPayload($entry),
        ]);
    }

    public function save(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'body' => ['nullable', 'string'],
            'sections' => ['nullable', 'array'],

            // sections[] optional, tapi kalau ada item -> id wajib
            'sections.*.id' => ['required_with:sections', 'string'],
            'sections.*.title' => ['nullable', 'string'],
            'sections.*.content' => ['nullable', 'string'],

            // user set reward sendiri (today only)
            'xp_reward' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'coin_reward' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);

        $todayKey = $this->todayKey();
        $isToday = $data['date'] === $todayKey;

        $xp = (int)($data['xp_reward'] ?? 0);
        $coin = (int)($data['coin_reward'] ?? 0);

        $entry = null;

        try {
            DB::transaction(function () use ($user, $data, $isToday, $xp, $coin, &$entry) {
                // lock row to prevent concurrent reward double-spend
                $entry = JournalEntry::where('user_id', $user->id)
                    ->whereDate('date', $data['date'])
                    ->lockForUpdate()
                    ->first();

                if (!$entry) {
                    $entry = new JournalEntry();
                    $entry->user_id = $user->id;
                    $entry->date = $data['date'];
                }

                // content always editable
                $entry->body = $data['body'] ?? null;
                $entry->sections = $data['sections'] ?? null;

                // Reward conditions:
                // - only today (Jakarta)
                // - not rewarded yet
                // - and at least one of xp/coin > 0 (avoid locking reward with 0)
                if ($isToday && !$entry->rewarded_at && ($xp > 0 || $coin > 0)) {
                    // Apply to profile once
                    if ($xp > 0) $user->profile()->increment('xp_total', $xp);
                    if ($coin > 0) $user->profile()->increment('coin_balance', $coin);

                    $entry->xp_awarded = $xp;
                    $entry->coin_awarded = $coin;
                    $entry->rewarded_at = now();
                }

                $entry->save();
            });
        } catch (UniqueConstraintViolationException $e) {
            // fallback: record already exists, just update content (no reward reapply)
            $entry = JournalEntry::where('user_id', $user->id)
                ->whereDate('date', $data['date'])
                ->firstOrFail();

            $entry->update([
                'body' => $data['body'] ?? null,
                'sections' => $data['sections'] ?? null,
            ]);
        }

        // redirect balik ke date yang sama, supaya props.entry ke-load/update
        return redirect()->to('/journal?date=' . $data['date'])
            ->with('success', 'Journal saved.');
    }
}
