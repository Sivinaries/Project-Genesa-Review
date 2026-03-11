<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // findOrFail / firstOrFail tidak ketemu → 404 JSON
        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan.',
                    'errors'  => null,
                ], 404);
            }
        });

        // Route tidak ada → 404 JSON
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Endpoint tidak ditemukan.',
                    'errors'  => null,
                ], 404);
            }
        });
    }

    /**
     * Validasi gagal di route /api/* → 422 JSON (bukan redirect).
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal.',
            'errors'  => $exception->errors(),
        ], 422);
    }

    /**
     * Unauthenticated di route /api/* → 401 JSON (bukan redirect ke login).
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Token tidak valid atau sudah expired.',
                'errors'  => null,
            ], 401);
        }

        return redirect()->guest(route('login'));
    }
}