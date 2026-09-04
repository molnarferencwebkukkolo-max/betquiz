<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleComingSoonMode;
use App\Services\ServerErrorAlertSpool;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleComingSoonMode::class,
        ]);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A webkérés csak egy kisméretű, érzékeny adatoktól megtisztított fájlt ír.
        // A tényleges e-mailt a scheduler külön folyamatban küldi el.
        $exceptions->report(function (\Throwable $exception): void {
            app(ServerErrorAlertSpool::class)->capture($exception, request());
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
