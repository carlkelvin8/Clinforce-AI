<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.rate_limit' => \App\Http\Middleware\ApiRateLimit::class,
            'require.sub' => \App\Http\Middleware\RequireActiveSubscription::class,
            'require.doc_access' => \App\Http\Middleware\RequireDocumentAccess::class,
        ]);
        // Apply to API group by default
        $middleware->appendToGroup('api', \App\Http\Middleware\ApiRateLimit::class);
        
        // Fix API authentication to return JSON instead of redirecting
        $middleware->redirectGuestsTo(fn ($request) => 
            $request->expectsJson() 
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
