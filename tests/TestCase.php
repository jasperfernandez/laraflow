<?php

declare(strict_types=1);

namespace JasperFernandez\Laraflow\Tests;

use Illuminate\Database\Eloquent\Model;
use JasperFernandez\Laraflow\Tests\Support\Models\Action;
use JasperFernandez\Laraflow\Tests\Support\Models\Status;
use Orchestra\Testbench\TestCase as Orchestra;
use JasperFernandez\Laraflow\LaraflowServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaraflowServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('laraflow.models.status', Status::class);
        $app['config']->set('laraflow.models.action', Action::class);

        Model::unguard();
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
