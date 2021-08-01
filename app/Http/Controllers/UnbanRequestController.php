<?php

namespace App\Http\Controllers;

use App\Admin\Mail\UnBanRequestMail;
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

        if (!$user->is_banned) {
            return redirect()->back()->with('error', 'You are not banned.');
        }

        if (!is_null($user->unbanned_at)) {
            return redirect()->back()->with('error', 'You are not banned forever.');
        }

        Cache::put('user-temp-' . $user->id, 'temp', now()->addMinutes(60));

        return view('request.request-unban-form', [
            'user' => $user
        ]);
    }

    public function requestForm(User $user) {
        if (!Cache::has('user-temp-' . $user->id)) {
            return redirect()->to('/')->with('error', 'Invalid input. Please start the unban request process again.');
        }

        return view('request.request-unban-form', [
            'user' => $user
        ]);
    }

    public function submitRequest(Request $request, User $user) {
        if (!Cache::has('user-temp-' . $user->id)) {
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
                Mail::to($adminUser->email)->send(new UnBanRequestMail($user));
            }

            Cache::delete('user-temp-' . $user->id);

            return redirect()->to('/')->with('success', 'Request submitted. We will contact you in the next 72 hours.');
        }

        return redirect()->to('/')->with('error', 'You already submitted a request. Future requests are ignored.');
    }
}
