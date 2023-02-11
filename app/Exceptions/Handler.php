<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

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
     *
     * @return void
     */
    public function register()
    {
        $errorResponse = function (Exception $e, $statusCode = 500) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $statusCode);
        };

        $this->renderable(function (BadRequestHttpException $e) use ($errorResponse) {
            return $errorResponse($e, 400);
        });

        $this->renderable(function (NotFoundHttpException $e) use ($errorResponse) {
            return $errorResponse($e, 404);
        });

        $this->renderable(function (UnauthorizedException $e) use ($errorResponse) {
            return $errorResponse($e, 401);
        });

        $this->renderable(function (Exception $e) use ($errorResponse) {
            return $errorResponse($e, 500);
        });

    }
}
