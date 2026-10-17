<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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
        // Shared-host MySQL caps index keys at 1000 bytes (innodb_large_prefix
        // off / older InnoDB). utf8mb4 (4 bytes/char) x 255 = 1020 > 1000, so
        // cap default string length at 191 (191x4 = 764 bytes) to keep indexed
        // varchar columns creatable. Only affects default-length columns.
        Schema::defaultStringLength(191);

        Gate::before(fn (User $user, string $ability): ?bool => $user->hasRole(Role::SUPER_ADMIN) ? true : null);
    }
}
