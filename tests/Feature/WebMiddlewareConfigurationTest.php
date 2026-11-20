<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

it('registers the essential web middleware', function (): void {
    app(Kernel::class);

    $middleware = app('router')
        ->getMiddlewareGroups()['web'];

    expect($middleware)->toContain(
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
        SubstituteBindings::class,
    );
});

it('applies essential middleware to application settings routes', function (): void {
    app(Kernel::class);

    $router = app('router');

    foreach (['settings.edit', 'settings.update'] as $name) {
        $route = $router->getRoutes()->getByName($name);

        $middleware = $router->gatherRouteMiddleware($route);

        expect($middleware)->toContain(
            StartSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
            SubstituteBindings::class,
        );
    }
});

it('applies session authentication to protected application routes', function (): void {
    app(Kernel::class);

    $router = app('router');

    foreach ([
        'dashboard',
        'students.index',
        'profile.edit',
        'profile.update',
        'profile.password.update',
        'settings.edit',
        'notifications.index',
    ] as $name) {
        $route = $router->getRoutes()->getByName($name);

        expect($route)->not->toBeNull();

        $middleware = $router->gatherRouteMiddleware($route);

        expect($middleware)
            ->toContain(AuthenticateSession::class);
    }
});
