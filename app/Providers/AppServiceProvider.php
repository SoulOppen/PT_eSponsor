<?php

namespace App\Providers;

use App\Models\Block;
use App\Policies\BlockPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The Phase 1 tests use the `Schema` facade, which expects the `db.schema`
        // container binding. Ensure it's available for the default connection.
        $this->app->singleton('db.schema', function ($app) {
            return $app['db']->connection()->getSchemaBuilder();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Gate::policy(Block::class, BlockPolicy::class);
    }
}
