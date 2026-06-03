<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Sessions;

use Padosoft\Rebel\Core\Contracts\DeviceTrust;
use Padosoft\Rebel\Core\Contracts\SessionRegistry;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Device/session registry for Laravel Rebel: session + refresh-token tracking with
 * rotation/reuse-detection, logout-everywhere, and device trust. Provides the default
 * implementations of the core SessionRegistry and DeviceTrust contracts.
 */
final class RebelSessionsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-rebel-sessions')
            ->hasConfigFile('rebel-sessions')
            ->hasMigration('create_rebel_sessions_table')
            ->hasMigration('create_rebel_devices_table');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SessionManager::class);
        $this->app->singleton(SessionRegistry::class, DatabaseSessionRegistry::class);
        $this->app->singleton(DeviceTrust::class, DatabaseDeviceTrust::class);
    }
}
