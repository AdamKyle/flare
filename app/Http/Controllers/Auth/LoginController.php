<?php

namespace App\Http\Controllers\Auth;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\SiteAccessedEvent;
use App\Flare\Handlers\CheatingCheck;
use App\Flare\Jobs\LoginMessage;
use App\Flare\Services\CanUserEnterSiteService;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    private $canUserEnterSiteService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CanUserEnterSiteService $canUserEnterSiteService)
    {
        $this->canUserEnterSiteService = $canUserEnterSiteService;

        $this->middleware('guest')->except('logout');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        if (!$this->canUserEnterSiteService->canUserEnterSite($request->{$this->username()})) {
            return redirect()->back()->with('error', 'I am sorry, right now the Registration and Login has been disabled while server maintenance and stability testing is taking place. We hope to be bck up and running soon!');
        }

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            if (!is_null($this->guard()->user()->character)) {

                $character = $this->guard()->user()->character;

                LoginMessage::dispatch($character)->delay(now()->addSeconds(5));
            }

            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

}
