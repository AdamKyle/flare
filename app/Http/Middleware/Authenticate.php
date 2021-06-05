<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware {

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param Request $request
     * @return string
     */
    protected function redirectTo($request) {
        if (! $request->expectsJson()) {
            event(new Logout('auth', null));

            return route('login');
        }
    }
}
