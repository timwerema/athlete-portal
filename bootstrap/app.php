<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // The theme cookie is read by the browser too, so it is not encrypted. It holds no personal data.
        $middleware->encryptCookies(except: ['appearance']);

        // Global, so error pages and file responses get the security headers too.
        $middleware->append(SecurityHeaders::class);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Friendly error pages that never show technical details.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            if (app()->environment('local') && config('app.debug') && $status === 500) {
                return $response;
            }

            if (in_array($status, [403, 404, 429, 500, 503], true) && ! $request->expectsJson()) {
                return Inertia::render('error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            if ($status === 419) {
                return back()->with('status', 'The page expired. Please try again.');
            }

            return $response;
        });
    })->create();
