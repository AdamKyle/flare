<?php

namespace App\Http\Controllers\Auth;

use App\Flare\Models\User;
use App\Http\Controllers\Controller;
use Auth;
use Cache;
use Hash;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Password;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/';

    public function reset(Request $request)
    {
        $request->validate($this->rules(), $this->validationErrorMessages());

        $user = User::where('email', $request->email)->first();

        if (is_null($user)) {
            return redirect()->back()->with('error', 'This email does not match our records.');
        }

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        if (!$user->hasRole('Admin')) {

            if ($response == Password::PASSWORD_RESET) {
                Auth::logout();

                Cache::put('user-' . $user->id, '', now()->addMinutes(5));

                return redirect()->to(route('user.reset.security.questions', [
                    'user' => $user
                ]));
            }

            return redirect()->back()->with('errors', 'Unable to complete password reset. Please try again. Make sure your passwords match.');
        }

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $response == Password::PASSWORD_RESET
                    ? $this->sendResetResponse($request, $response)
                    : $this->sendResetFailedResponse($request, $response);
    }

    public function resetSecuityQuestions(Request $request, User $user) {
        return view('auth.reset-security-questions', [
            'user' => $user
        ]);
    }

    public function updateSecurityQuestions(Request $request, User $user) {
        $validReset = Cache::pull('user-' . $user->id);

        if (is_null($validReset)) {
            return redirect()->to('/')->with('error', 'Unable to process password reset. Please start again by following the forgot password link on the login page.');
        }

        $user->securityQuestions()->truncate();

        if ($request->question_one === $request->question_two) {
            return redirect()->back()->with('error', 'Security questions need to be unique.');
        }

        if ($request->answer_one === $request->answer_two) {
            return redirect()->back()->with('error', 'Security questions answers need to be unique.');
        }

        $user = $this->createSecurityQuestions($request, $user);

        Auth::login($user);
        
        return redirect()->to('/');
    }

    protected function createSecurityQuestions(Request $request, User $user): User {
        $user->securityQuestions()->insert([
            [
                'user_id'    => $user->id,
                'question'   => $request->question_one,
                'answer'     => Hash::make($request->answer_one),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id'    => $user->id,
                'question'   => $request->question_two,
                'answer'     => Hash::make($request->answer_two),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        return $user->refresh();
    }
}
