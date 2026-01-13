<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Quest extends Model
{
    //
    protected $fillable = [
        'user_id',
        'name',
        'status',
        'type',
        'xp_reward',
        'coin_reward',
        'due_date',
        'completed_at',
        'is_repeatable'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function completions()
    {
        return $this->hasMany(QuestCompletion::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', ['todo', 'in_progress'])
              ->orderByRaw('due_date is null, due_date asc') // Deadline paling dekat di atas, yang null di bawah
              ->latest(); // Kalau deadline sama, yang baru dibuat di atas
    }
}
