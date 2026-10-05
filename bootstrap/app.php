<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SetLocale;
use App\Listeners\RecordUserActivity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AddSecurityHeaders::class);
        $middleware->web(append: [SetLocale::class]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Pengunjung yang belum login diarahkan ke form login, bukan diberi 404.
        $middleware->redirectGuestsTo(fn () => route('login'));

        // User yang sudah login tidak perlu melihat halaman login/register lagi.
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Halaman 403/404 untuk pengguna browser, JSON untuk request API.
        $exceptions->shouldRenderJsonWhen(
            fn ($request, $exception) => $request->expectsJson() || $request->is('api/*')
        );

        // Jangan bocorkan detail internal saat APP_DEBUG=false.
        $exceptions->dontReport([
            AuthenticationException::class,
            AuthorizationException::class,
            HttpExceptionInterface::class,
        ]);
    })->create();

Event::listen(Login::class, [RecordUserActivity::class, 'handleLogin']);
Event::listen(Logout::class, [RecordUserActivity::class, 'handleLogout']);
