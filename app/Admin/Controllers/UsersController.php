<?php

namespace App\Admin\Controllers;

use App\Admin\Mail\ResetPasswordEmail;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Jobs\UpdateSilencedUserJob;
use App\Flare\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Mail;

class UsersController extends Controller {

    public function index() {
        return view('admin.users.users');
    }

    public function resetPassword(User $user) {

        $password = Str::random(80);

        $user->update([
            'password' => Hash::make($password)
        ]);

        $token = app('Password')::getRepository()->create($user);

        Mail::to($user->email)->send(new ResetPasswordEmail($user, $token));

        return redirect()->back()->with('success', $user->character->name . ' password reset email sent.');
    }

    public function show(User $user) {

        if ($user->hasRole('Admin')) {
            return redirect()->back()->with('error', 'Admins do not have characters');
        }

        return view('admin.users.user', [
            'character' => $user->character,
        ]);
    }

    public function silenceUser(Request $request, User $user) {
        if (!$request->has('silence_for')) {
            return redirect()->back()->with('error', 'Invalid input.');
        }

        $canSpeakAgainAt = now()->addMinutes((int) $request->silence_for);

        $user->update([
            'is_silenced' => true,
            'can_speak_again_at' => $canSpeakAgainAt,
        ]);

        $user = $user->refresh();

        $message = 'The creator has silenced you until: ' . $canSpeakAgainAt->format('Y-m-d H:i:s') . ' ('.(int) $request->silence_for.' Minutes server time) Making accounts to get around this is a bannable offense.';
        event(new ServerMessageEvent($user, 'silenced', $message));

        UpdateSilencedUserJob::dispatch($user)->delay($canSpeakAgainAt);

        return redirect()->back()->with('success', $user->character->name . ' Has been silenced for: ' . (int) $request->silence_for . ' minutes');
    }
}
