<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // No Supervisor/Redis on shared hosting: cron runs `schedule:run` every
        // minute. Drain the `database` queue to empty, bounded, and hold an
        // application-level lock so two ticks can never overlap and run the
        // same job twice. File-based lock store (default cache is `database`,
        // which has its own lock table — avoid the bootstrap ordering issue).
        $schedule->command('queue:work', ['--stop-when-empty' => true, '--max-time' => 50])
            ->everyMinute()
            ->withoutOverlapping(30)
            ->runInBackground();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'role' => EnsureRole::class,
        ]);

        $middleware->web(append: [SecurityHeaders::class]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'deploy/migrate',
            'auth/bridge/events',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
