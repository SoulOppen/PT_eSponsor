<?php

namespace App\Providers;

use App\Models\Block;
use App\Policies\BlockPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
         * Needs: application container instance during service registration.
         * Does: binds db.schema singleton using default connection schema builder.
         * Returns: void.
         */
        $this->app->singleton('db.schema', function ($app) {
            return $app['db']->connection()->getSchemaBuilder();
        });
    }

    public function boot(): void
    {
        /*
         * Needs: boot phase with Vite and Gate facades available.
         * Does: enables Vite prefetching and registers Block policy mapping.
         * Returns: void.
         */
        Vite::prefetch(concurrency: 3);

        Gate::policy(Block::class, BlockPolicy::class);
    }
}
