<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prize extends Model
{
    protected $fillable = ['house_id', 'name'];

    public function house()
    {
        return $this->belongsTo(House::class);
    }
}
