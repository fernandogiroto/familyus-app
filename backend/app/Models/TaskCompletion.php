<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskCompletion extends Model
{
    protected $fillable = ['task_id', 'user_id', 'status', 'image_path', 'started_at', 'completed_at', 'week_start', 'completion_date'];

    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime', 'completion_date' => 'date'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
