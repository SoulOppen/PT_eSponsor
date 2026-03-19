<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\DatabaseServiceProvider;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Facade;

//$app = ...
$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withProviders([
        DatabaseServiceProvider::class,
        AppServiceProvider::class,
    ])
    ->create();

// The Schema facade requires this binding. Some Laravel 11 bootstraps in this repo
// don't register it automatically, so ensure it exists for test assertions.
$app->singleton('db.schema', function ($app) {
    return $app['db']->connection()->getSchemaBuilder();
});

Facade::setFacadeApplication($app);

return $app;
