<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['house_id', 'created_by', 'name', 'score', 'weekly_frequency', 'category', 'is_active'];

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completions()
    {
        return $this->hasMany(TaskCompletion::class);
    }

    public function currentCompletion()
    {
        $weekStart = now()->startOfWeek()->toDateString();
        return $this->hasOne(TaskCompletion::class)->where('week_start', $weekStart)->latest();
    }
}
