<?php

namespace App\Policies;

use App\Models\House;
use App\Models\User;

class HousePolicy
{
    public function view(User $user, House $house): bool
    {
        return $house->users()->where('user_id', $user->id)->exists();
    }
}
