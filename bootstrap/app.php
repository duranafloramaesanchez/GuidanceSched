<?php

use App\Http\Middleware\AppendMis215EggHeader;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            AppendMis215EggHeader::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = match (true) {
                $exception instanceof ValidationException => 400,
                $exception instanceof AuthenticationException => 401,
                $exception instanceof AuthorizationException => 403,
                $exception instanceof ModelNotFoundException, $exception instanceof NotFoundHttpException => 404,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode() === 422
                    ? 400
                    : $exception->getStatusCode(),
                default => 500,
            };
            $code = match ($status) {
                400, 422 => 'BAD_REQUEST',
                401 => 'UNAUTHENTICATED',
                403 => 'FORBIDDEN',
                404 => 'NOT_FOUND',
                default => 'INTERNAL_SERVER_ERROR',
            };
            $message = match ($status) {
                400, 422 => 'The request could not be processed.',
                401 => 'Authentication is required.',
                403 => 'You are not allowed to perform this action.',
                404 => 'The requested resource was not found.',
                default => 'An unexpected server error occurred.',
            };

            $body = ['error' => ['code' => $code, 'message' => $message]];
            if ($exception instanceof ValidationException) {
                $body['error']['details'] = $exception->errors();
            }

            $response = response()->json($body, $status);
            if ($status === 401) {
                $response->headers->set('WWW-Authenticate', 'Basic realm="GuidanceSched API"');
            }

            return $response;
        });
    })->create();
