<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class House extends Model
{
    protected $fillable = ['name', 'icon', 'invite_code', 'created_by', 'status', 'start_date', 'game_started_at'];

    protected $casts = ['start_date' => 'date', 'game_started_at' => 'datetime'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($house) {
            $house->invite_code = strtoupper(Str::random(8));
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'house_users')
            ->withPivot(['is_ready', 'weekly_score'])
            ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function prizes()
    {
        return $this->hasMany(Prize::class);
    }

    public function weeklyWinners()
    {
        return $this->hasMany(WeeklyWinner::class);
    }
}
