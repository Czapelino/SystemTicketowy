<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('admin-only', function (User $user) {
            return $user->role === 'admin';
        });

        // Share the escalated-ticket count with every admin panel view,
        // so the sidebar badge stays consistent without every controller
        // having to remember to pass it.
        View::composer('admin.*', function ($view) {
            $view->with(
                'sidebarEscalatedCount',
                Ticket::where('status', Ticket::STATUS_ESCALATED)->count()
            );
        });
    }
}
