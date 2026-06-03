<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Sessions;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Skeleton iniziale di padosoft/laravel-rebel-sessions. Implementazione in arrivo.
 */
final class RebelSessionsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-rebel-sessions');
    }
}
