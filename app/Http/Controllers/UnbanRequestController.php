<?php

namespace App\Http\Controllers;

use App\Admin\Mail\UnbanRequest;
use App\Flare\Models\User;
use Cache;
use Hash;
use Illuminate\Http\Request;
use Mail;

class UnbanRequestController extends Controller
{

    public function unbanRequest() {
        return view('request.unban');
    }

    public function findUser(Request $request) {
        $request->validate([
            'email' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (is_null($user)) {
            return redirect()->back()->with('error', 'This email does not match our records.');
        }

        Cache::put('user-temp-' . $user->id, 'temp', now()->addMinutes(60));

        return redirect()->to(route('un.ban.security.check', $user));
    }

    public function securityForm(User $user) {
        if (is_null(Cache::get('user-temp-' . $user->id))) {
            return redirect()->to('/')->with('error', 'Invalid input. Please start the unban request process again.');
        }

        return view('request.unban-security-check', [
            'user' => $user
        ]);
    }

    public function securityCheck(Request $request, User $user) {
        if (is_null(Cache::get('user-temp-' . $user->id))) {
            return redirect()->to('/')->with('error', 'Invalid input. Please start the unban request process again.');
        }

        $request->validate([
            'answer_one' => 'required',
            'answer_two' => 'required',
        ]);
        
        if (is_null(Cache::get('user-temp-' . $user->id))) {
            return redirect()->to('/')->with('error', 'Invalid input. Please start the unban request process again.');
        }

        $firstAnswer  = $user->securityQuestions()->where('question', $request->question_one)->first()->answer;
        $secondAnswer = $user->securityQuestions()->where('question', $request->question_two)->first()->answer;

        if (!Hash::check($request->answer_one, $firstAnswer) && !Hash::check($request->answer_two, $secondAnswer)) {
            return redirect()->back()->with('error', 'The answer to one or more security questions does not match our records.');
        }

        return redirect()->to(route('un.ban.request.form', [
            'user' => $user
        ]));
    }

    public function requestForm(User $user) {
        if (is_null(Cache::get('user-temp-' . $user->id))) {
            return redirect()->to('/')->with('error', 'Invalid input. Please start the unban request process again.');
        }

        return view('request.request-unban-form', [
            'user' => $user
        ]);
    }

    public function submitRequest(Request $request, User $user) {
        if (is_null(Cache::get('user-temp-' . $user->id))) {
            return redirect()->to('/')->with('error', 'Invalid input. Please start the unban request process again.');
        }
        
        $request->validate([
            'unban_message' => 'required'
        ]);

        if (is_null($user->un_ban_request)) {
            $user->update([
                'un_ban_request' => $request->unban_message
            ]);

            foreach (User::role('Admin')->get() as $adminUser) {
                Mail::to($adminUser->email)->send(new UnBanRequest($user));
            }

            return redirect()->to('/')->with('success', 'Request submitted. We will contact you in the next 72 hours.');
        }

        return redirect()->to('/')->with('error', 'You already submitted a request. Future requests are ignored.');
    }
}
