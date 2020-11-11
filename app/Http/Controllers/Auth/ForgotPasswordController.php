<?php

namespace App\Http\Controllers\Auth;

use App\Flare\Models\User;
use App\Http\Controllers\Controller;
use Cache;
use Hash;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Password;

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

        Cache::put($user->id . '-email', $request->email, now()->addMinutes(5));

        return redirect()->to(route('user.security.questions', [
            'user' => $user->id
        ]));
    }

    public function answerSecurityQuestions(Request $request, User $user) {
        $email = Cache::get($user->id . '-email');

        if (is_null($email)) {
            return redirect()->to('/')->with('error', 'Your time expired. Please try again.');
        }

        return view('auth.passwords.answer-security-questions', [
            'user' => $user,
        ]);
    }

    public function securityQuestionsAnswers(Request $request, User $user) {
        $email = Cache::get($user->id . '-email');

        if (is_null($email)) {
            return redirect()->to('/')->with('error', 'Your time expired. Please try again.');
        }

        $firstAnswer = $user->securityQuestions()->where('question', $request->question_one)->first()->answer;
        $secondAnswer = $user->securityQuestions()->where('question', $request->question_two)->first()->answer;

        if (!Hash::check($request->answer_one, $firstAnswer) && !Hash::check($request->answer_two, $secondAnswer)) {
            return redirect()->back()->with('error', 'The answer to one or more security questions does not match our records.');
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->broker()->sendResetLink(
            $this->credentials($request)
        );

        Cache::delete($user->id . '-email');

        return $response == Password::RESET_LINK_SENT
                    ? redirect()->to('/')->with('success', 'Sent you an email to begin the reset process.')
                    : redirect()->to('/')->with('error', 'Failed to send link.');
    }
}
