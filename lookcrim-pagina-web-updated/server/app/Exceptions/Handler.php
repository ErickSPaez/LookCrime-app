<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, $request) {
            if (!$request->isMethod('GET')) {
                return null;
            }

            if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                return null;
            }

            if (!Auth::check()) {
                return null;
            }

            $statusCode = null;
            if ($e instanceof AuthorizationException) {
                $statusCode = $e->status() ?? 403;
            } elseif ($e instanceof HttpExceptionInterface) {
                $statusCode = $e->getStatusCode();
            }

            if ($statusCode !== 403) {
                return null;
            }

            $routeName = $request->route()?->getName();
            $shouldRedirect =
                $request->is('registers') ||
                $request->is('map') ||
                $request->is('settings/roles*') ||
                $request->is('settings/city*') ||
                ($routeName === 'registers.index') ||
                ($routeName === 'registers.map') ||
                (is_string($routeName) && str_starts_with($routeName, 'settings.roles.')) ||
                (is_string($routeName) && str_starts_with($routeName, 'settings.city.'));

            if (!$shouldRedirect) {
                return null;
            }

            return redirect()->route('no-access');
        });
    }
}
