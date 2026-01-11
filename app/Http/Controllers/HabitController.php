<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Illuminate\Http\Request;

class HabitController extends Controller
{
    public function toggleToday(Request $request, Habit $habit)
    {
        // ownership check
        abort_unless($habit->user_id === $request->user()->id, 403);

        // habit harus aktif dan sudah mulai
        $today = now()->toDateString();

        if ($habit->start_date > $today) {
            return redirect()->back();
        }

        if ($habit->end_date && $habit->end_date < $today) {
            return redirect()->back();
        }

        // toggle entry (create kalau belum ada, delete kalau sudah ada)
        $entry = $request->user()->habitEntries()
            ->where('habit_id', $habit->id)
            ->whereDate('date', $today)
            ->first();

        if ($entry) {
            $entry->delete();
        } else {
            $request->user()->habitEntries()->create([
                'habit_id' => $habit->id,
                'date' => $today,
            ]);
        }

        return redirect()->back();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
        ]);

        $request->user()->habits()->create([
            'name' => $data['name'],
            'start_date' => $data['start_date'] ?? now()->toDateString(),
            'end_date' => null,
        ]);

        return redirect()->back();
    }

    public function archive(Request $request, Habit $habit)
    {
        abort_unless($habit->user_id === $request->user()->id, 403);

        if ($habit->end_date) {
            return redirect()->back();
        }

        $habit->update([
            'end_date' => now()->toDateString(),
        ]);

        return redirect()->back();
    }

    public function toggleDate(Request $request, Habit $habit)
    {
        abort_unless($habit->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = $data['date'];

        // batas valid: >= start_date, dan <= min(today, end_date kalau ada)
        $maxDate = now()->toDateString();
        if ($habit->end_date && $habit->end_date < $maxDate) {
            $maxDate = $habit->end_date;
        }

        if ($date < $habit->start_date) return redirect()->back();
        if ($date > $maxDate) return redirect()->back();

        $entry = $request->user()->habitEntries()
            ->where('habit_id', $habit->id)
            ->whereDate('date', $date)
            ->first();

        if ($entry) {
            $entry->delete();
        } else {
            $request->user()->habitEntries()->create([
                'habit_id' => $habit->id,
                'date' => $date,
            ]);
        }

        return redirect()->back();
    }
}
