<?php

namespace App\Http\Controllers;

use App\Admin\Mail\UnBanRequestMail;
use App\Flare\Models\User;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Monolog\Handler\MailHandler;

class UnbanRequestController extends Controller
{
    public function unbanRequest()
    {
        return view('request.unban');
    }

    public function findUser(Request $request)
    {
        $request->validate([
            'email' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();
        $token = Str::random(64);
        $userId = 0;

        if (! is_null($user) && $user->is_banned && is_null($user->unbanned_at)) {
            $userId = $user->id;
        }

        Cache::put('unban-request-token-'.$token, $userId, now()->addMinutes(60));

        return redirect()->route('un.ban.request')
            ->with('success', 'If this account is eligible, you may continue with an unban request.')
            ->with('unban_request_token', $token);
    }

    public function requestForm(string $token)
    {
        if (! Cache::has('unban-request-token-'.$token)) {
            return redirect()->route('un.ban.request')->with('error', 'Unable to submit that request.');
        }

        return view('request.request-unban-form', [
            'token' => $token,
        ]);
    }

    public function submitRequest(Request $request)
    {
        $request->validate([
            'unban_message' => 'required',
        ]);

        $submittedToken = $request->input('token');

        if (! is_string($submittedToken)) {
            return redirect()->back()->with('error', 'Unable to submit that request.');
        }

        $userId = Cache::pull('unban-request-token-'.$submittedToken);

        if (is_null($userId)) {
            return redirect()->back()->with('error', 'Unable to submit that request.');
        }

        $user = $userId ? User::find($userId) : null;

        if (
            ! is_null($user) &&
            $user->is_banned &&
            is_null($user->unbanned_at) &&
            is_null($user->un_ban_request)
        ) {
            $user->update([
                'un_ban_request' => $request->unban_message,
            ]);

            foreach (User::role('Admin')->get() as $adminUser) {
                MailHandler::dispatch($adminUser->email, new UnBanRequestMail($user))->delay(now()->addMinutes(1));
            }
        }

        return redirect()->to('/')->with('success', 'Request submitted. We will contact you in the next 72 hours.');
    }
}
