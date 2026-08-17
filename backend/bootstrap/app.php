<?php

declare(strict_types=1);

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\VerifyTelegramWebApp;
use App\Http\Middleware\VerifyTelegramWebhook;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

//Todo remove temp test fixes after ci/cd implementation
$isTesting = (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] === 'testing')
    || (getenv('APP_ENV') === 'testing')
    || defined('PHPUNIT_COMPOSER_INSTALL')
    || (isset($_SERVER['argv']) && (str_contains(implode(' ', $_SERVER['argv']), 'phpunit') || str_contains(implode(' ', $_SERVER['argv']), 'test')));

if ($isTesting) {
    putenv('DB_CONNECTION=sqlite');
    putenv('DB_DATABASE=:memory:');
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_ENV['DB_DATABASE'] = ':memory:';
    $_SERVER['DB_CONNECTION'] = 'sqlite';
    $_SERVER['DB_DATABASE'] = ':memory:';
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->alias([
            'tg_auth' => VerifyTelegramWebApp::class,
            'tg_webhook' => VerifyTelegramWebhook::class,
            'subscription' => CheckSubscription::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
