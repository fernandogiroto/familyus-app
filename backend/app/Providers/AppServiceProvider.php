<?php

namespace App\Providers;

use App\Models\House;
use App\Policies\HousePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(House::class, HousePolicy::class);
    }
}
