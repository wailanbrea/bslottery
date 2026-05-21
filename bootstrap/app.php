<?php

use App\Http\Middleware\EnsureActiveCompanyAndBranch;
use App\Http\Middleware\EnsureDeviceIsAuthorized;
use App\Http\Middleware\EnsureInitialSetupIsCompleted;
use App\Http\Middleware\EnsureLicenseIsValid;
use App\Http\Middleware\EnsurePasswordWasChanged;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\SecurityHeaders;
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
        // Global middleware runs before any route/group middleware, including auth
        $middleware->append([
            SecurityHeaders::class,
            EnsureLicenseIsValid::class,
            EnsureInitialSetupIsCompleted::class,
        ]);

        // Web-only: redirects to a Blade page, not applicable to API
        $middleware->web(append: [
            EnsurePasswordWasChanged::class,
        ]);

        $middleware->alias([
            'active.context' => EnsureActiveCompanyAndBranch::class,
            'permission' => EnsureUserHasPermission::class,
            'device.authorized' => EnsureDeviceIsAuthorized::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
