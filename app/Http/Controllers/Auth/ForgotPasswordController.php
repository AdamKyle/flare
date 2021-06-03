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

        Cache::put($user->id . '-email', $request->email, now()->addMinutes(5));

        return redirect()->to(route('user.security.questions', [
            'user' => $user->id
        ]));
    }

    public function answerSecurityQuestions(Request $request, User $user) {
        if (!Cache::has($user->id . '-email')) {
            return redirect()->to('/')->with('error', 'Your time expired. Please try again.');
        }

        return view('auth.passwords.answer-security-questions', [
            'user' => $user,
        ]);
    }

    public function securityQuestionsAnswers(Request $request, User $user) {
        if (!Cache::has($user->id . '-email')) {
            return redirect()->to('/')->with('error', 'Your time expired. Please try again.');
        }

        $firstAnswer = $user->securityQuestions()->where('question', $request->question_one)->first()->answer;
        $secondAnswer = $user->securityQuestions()->where('question', $request->question_two)->first()->answer;

        if (!Hash::check($request->answer_one, $firstAnswer) && !Hash::check($request->answer_two, $secondAnswer)) {
            return redirect()->back()->with('error', 'The answer to one or more security questions does not match our records.');
        }

        $token = app('Password')::getRepository()->create($user);

        Mail::to($user->email)->from(config('mail.username'), 'Planes of Tlessa')->send(new ResetPassword($user, $token));

        Cache::delete($user->id . '-email');

        return redirect()->to('/')->with('success', 'Sent you an email to begin the reset process.');
    }
}
