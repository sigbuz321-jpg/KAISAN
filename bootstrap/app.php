<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Laravel sends unauthenticated visitors to a route named `login`.
        // Every user-facing route here is named in Indonesian, so without this
        // the auth middleware threw "Route [login] not defined" and every
        // protected page answered 500 instead of a redirect.
        $middleware->redirectGuestsTo(fn () => route('masuk'));

        // Caddy terminates TLS and forwards to PHP-FPM over plain HTTP. Without
        // this Laravel builds http:// URLs on an https:// site, which breaks
        // asset links, redirects after login, and Livewire's update endpoint.
        // Trusting any proxy is safe here because the app port is published to
        // 127.0.0.1 only, so nothing but the local proxy can reach it.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
