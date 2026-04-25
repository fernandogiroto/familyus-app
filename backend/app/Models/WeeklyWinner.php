<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyWinner extends Model
{
    protected $fillable = ['house_id', 'user_id', 'week_start', 'is_tie', 'prize_id', 'prize_name', 'prize_selected_at'];

    protected $casts = ['prize_selected_at' => 'datetime', 'week_start' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prize()
    {
        return $this->belongsTo(Prize::class);
    }
}
