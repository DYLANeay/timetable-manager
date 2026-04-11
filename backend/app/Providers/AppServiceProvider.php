<?php

namespace App\Providers;

use App\Models\LeaveRequest;
use App\Models\Shift;
use App\Models\SwapRequest;
use App\Observers\LeaveRequestObserver;
use App\Observers\ShiftObserver;
use App\Observers\SwapRequestObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        SwapRequest::observe(SwapRequestObserver::class);
        LeaveRequest::observe(LeaveRequestObserver::class);
        Shift::observe(ShiftObserver::class);
    }
}
