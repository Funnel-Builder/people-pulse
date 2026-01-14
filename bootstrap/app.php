<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Configure redirect for unauthenticated users
        $middleware->redirectGuestsTo(function (Request $request) {
            // If we are in a tenant context (subdomain), redirect to local login
            if (tenant()) {
                return route('tenant.login');
            }
            // Otherwise central login
            return route('login');
        });

        // Configure redirect for authenticated users (e.g., when accessing login page while logged in)
        $middleware->redirectUsersTo(function (Request $request) {
            $tenant = tenant();
            if ($tenant) {
                return "/dashboard";
            }
            return '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
