<?php

namespace App\Exceptions;

use App\Game\Character\Exceptions\MissingInventoryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * @codeCoverageIgnore
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request  $request
     * @param  \Exception  $exception
     * @return Response
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof MissingInventoryException) {
            Auth::logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your previous character no longer exists. Please create a new character.',
                ], 410);
            }

            return redirect()->to('/login')->with('error', 'Your previous character no longer exists. Please create a new character.');
        }

        if ($exception instanceof TokenMismatchException) {
            return redirect()->to('/')->with('error', 'You were logged out due to inactivity. Please login again.');
        }

        return parent::render($request, $exception);
    }
}
