<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class JournalArchivePageController extends Controller
{
    private function todayKey(): string
    {
        return Carbon::now('Asia/Jakarta')->toDateString(); // YYYY-MM-DD
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // month = YYYY-MM (Jakarta). default = bulan ini (Jakarta)
        $month = $request->query('month', Carbon::now('Asia/Jakarta')->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = Carbon::now('Asia/Jakarta')->format('Y-m');
        }

        $start = Carbon::createFromFormat('Y-m', $month, 'Asia/Jakarta')->startOfMonth()->toDateString();
        $end   = Carbon::createFromFormat('Y-m', $month, 'Asia/Jakarta')->endOfMonth()->toDateString();

        // Ambil list tanggal yg ada entry (buat dot di kalender)
        $filledDays = JournalEntry::where('user_id', $user->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get(['date'])
            ->map(fn($e) => $e->date->toDateString())
            ->values();

        // Helper kecil buat bikin headline (server-side biar konsisten)
        $makeHeadline = function (JournalEntry $e): string {
            $text = '';

            // prioritas: body
            $text = trim((string)($e->body ?? ''));

            // fallback: section pertama yang ada isi
            if ($text === '') {
                foreach (($e->sections ?? []) as $s) {
                    $c = trim((string)($s['content'] ?? ''));
                    if ($c !== '') {
                        $text = $c;
                        break;
                    }
                }
            }

            // bersihin newline & limit
            $text = preg_replace("/\s+/", " ", $text);
            return mb_strimwidth($text ?: '...', 0, 80, '...');
        };

        // Entries list untuk bulan ini (main UX)
        $entries = JournalEntry::where('user_id', $user->id)
            ->whereBetween('date', [$start, $end])
            ->orderByDesc('date')
            ->get(['id', 'date', 'title', 'rewarded_at', 'body', 'sections'])
            ->map(fn($e) => [
                'id' => $e->id,
                'date' => $e->date->toDateString(),
                'title' => trim((string)($e->title ?? '')) !== '' ? $e->title : $e->date->toDateString(),
                'headline' => $makeHeadline($e),
                'rewarded_at' => optional($e->rewarded_at)?->toISOString(),
            ])
            ->values();


        return Inertia::render('Journal/Archive', [
            'month' => $month,                 // YYYY-MM
            'todayKey' => $this->todayKey(),   // YYYY-MM-DD
            'filledDays' => $filledDays,       // array of YYYY-MM-DD
            'entries' => $entries,
        ]);
    }
}
