<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    //
    protected $fillable = [
        'user_id',
        'xp_total',
        'coin_balance',
        'current_streak',
        'last_quest_completed_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
