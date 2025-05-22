<?php

namespace Mateffy\LaravelCodebaseMcp;

use Mateffy\LaravelCodebaseMcp\Commands\IntrospectMcpCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelCodebaseMcpServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-codebase-mcp')
            ->hasCommand(IntrospectMcpCommand::class);
    }
}
