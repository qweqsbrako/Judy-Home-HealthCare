<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define Gates for role-based permissions
        Gate::define('access-admin-panel', function (User $user) {
            return $user->hasAnyRole(['admin', 'superadmin']);
        });

        Gate::define('verify-users', function (User $user) {
            return $user->hasAnyRole(['admin', 'superadmin']);
        });

        Gate::define('manage-nurses', function (User $user) {
            return $user->hasAnyRole(['admin', 'superadmin']);
        });

        Gate::define('manage-patients', function (User $user) {
            return $user->hasAnyRole(['admin', 'superadmin', 'doctor', 'nurse']);
        });

        Gate::define('view-patient-data', function (User $user) {
            return $user->hasAnyRole(['doctor', 'nurse', 'admin', 'superadmin']);
        });

        Gate::define('create-care-plans', function (User $user) {
            return $user->hasAnyRole(['doctor', 'admin', 'superadmin']);
        });

        Gate::define('update-care-plans', function (User $user) {
            return $user->hasAnyRole(['doctor', 'admin', 'superadmin']);
        });

        Gate::define('access-system-settings', function (User $user) {
            return $user->isSuperAdmin();
        });

        Gate::define('view-audit-logs', function (User $user) {
            return $user->hasAnyRole(['admin', 'superadmin']);
        });

        Gate::define('manage-system-users', function (User $user) {
            return $user->isSuperAdmin();
        });
    }
}