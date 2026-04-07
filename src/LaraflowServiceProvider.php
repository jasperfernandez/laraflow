<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow;

use JasperFernandez\Laraflow\Commands\LaraflowCommand;
use JasperFernandez\Laraflow\Contracts\WorkflowAuthorization;
use JasperFernandez\Laraflow\Contracts\WorkflowDefinitionRepository;
use JasperFernandez\Laraflow\Repositories\EloquentWorkflowDefinitionRepository;
use JasperFernandez\Laraflow\Services\RoleBasedWorkflowAuthorization;
use JasperFernandez\Laraflow\Services\WorkflowEngine;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class LaraflowServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laraflow')
            ->hasConfigFile()
            ->hasMigration('create_laraflow_tables')
            ->hasCommand(LaraflowCommand::class);
    }

    public function registeringPackage(): void
    {
        $this->app->bind(
            WorkflowDefinitionRepository::class,
            EloquentWorkflowDefinitionRepository::class,
        );

        $this->app->bind(
            WorkflowAuthorization::class,
            RoleBasedWorkflowAuthorization::class,
        );

        $this->app->singleton(
            WorkflowEngine::class,
            WorkflowEngine::class,
        );
    }
}
