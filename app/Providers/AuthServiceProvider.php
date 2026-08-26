<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Invoice;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Invoice::class => \App\Policies\InvoicePolicy::class,
        User::class => \App\Policies\UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // ============ Define Gates (Optional) ============
        
        // Admin only gate
        Gate::define('admin-only', function (User $user) {
            return $user->role === 'admin';
        });

        // Manage invoices gate
        Gate::define('manage-invoices', function (User $user) {
            return in_array($user->role, ['admin', 'accountant']);
        });

        // View invoices gate
        Gate::define('view-invoices', function (User $user) {
            return in_array($user->role, ['admin', 'accountant', 'viewer']);
        });
    }
}