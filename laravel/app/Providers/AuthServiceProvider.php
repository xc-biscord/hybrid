<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Server;
use App\Models\User;
use App\Policies\ServerPolicy;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Registers Laravel's authorization primitives (Gates / Policies) as a
 * thin integration layer over the existing PermissionService and
 * ModerationService. No business rule lives here; every callback
 * delegates to the underlying services.
 *
 * See docs/mvc-migration/laravel-authorization.md for the full mapping
 * and the list of checks that still live in legacy code paths.
 */
final class AuthServiceProvider extends ServiceProvider
{
    /**
     * Explicit policy map. Auto-discovery is skipped because the current
     * domain objects (Server, ServerMember) are plain PHP models, not
     * Eloquent, so we wire them by hand.
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Server::class => ServerPolicy::class,
    ];

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        $this->registerGlobalGates();
    }

    /**
     * Global (non-resource-scoped) gates. These exist so ad-hoc checks
     * outside of a specific resource — e.g. the P1 admin guard on admin
     * endpoints — can use Gate::allows() instead of calling
     * PermissionService directly.
     */
    private function registerGlobalGates(): void
    {
        Gate::define('is-p1', function (User $user): bool {
            /** @var PermissionService $permissions */
            $permissions = app(PermissionService::class);

            return $permissions->isP1((int) $user->id);
        });

        Gate::define('server.has-role', function (User $user, int $serverId, array $requiredRoles): bool {
            /** @var PermissionService $permissions */
            $permissions = app(PermissionService::class);

            return $permissions->hasPermission((int) $user->id, $serverId, $requiredRoles);
        });
    }
}
