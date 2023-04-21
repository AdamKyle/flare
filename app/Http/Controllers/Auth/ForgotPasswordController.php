<?php

namespace App\Http\Controllers\Auth;

use Cache;
use Hash;
use Mail;
use Password;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use App\Flare\Mail\ResetPassword;
use App\Flare\Models\User;
use App\Http\Controllers\Controller;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        $user = User::where('email', $request->email)->first();

        if (is_null($user)) {
            return redirect()->back()->with('error', 'This email does not match our records.');
        }

        if ($user->hasRole('Admin')) {
            $response = $this->broker()->sendResetLink(
                $this->credentials($request)
            );

            return $response == Password::RESET_LINK_SENT
                        ? redirect()->to('/')->with('success', 'Sent you an email to begin the reset process.')
                        : redirect()->to('/')->with('error', 'Failed to send link.');
        }

        $token = app('Password')::getRepository()->create($user);

        Mail::to($user->email)->send((new ResetPassword($user, $token)));

        return redirect()->to('/')->with('success', 'Sent you an email to begin the reset process.');
    }
}
